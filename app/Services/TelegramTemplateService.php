<?php

namespace App\Services;

use App\Enums\TelegramNotificationType;
use Illuminate\Support\Facades\Log;

/**
 * TelegramTemplateService
 * 
 * Dynamic message template system for Telegram notifications with:
 * - Role-specific message customization
 * - Template inheritance and composition
 * - Conditional content based on data
 * - Multi-language support (future)
 */
class TelegramTemplateService
{
    protected array $templates = [];
    protected array $roleCustomizations = [];

    public function __construct()
    {
        $this->loadTemplates();
        $this->loadRoleCustomizations();
    }

    /**
     * Generate message from template
     */
    public function generateMessage(string $notificationType, array $data, ?string $targetRole = null): string
    {
        $template = $this->getTemplate($notificationType, $targetRole);
        
        if (!$template) {
            Log::warning("No template found for notification type: {$notificationType}");
            return $this->generateFallbackMessage($notificationType, $data);
        }

        return $this->renderTemplate($template, $data, $targetRole);
    }

    /**
     * Get template with role-specific customizations
     */
    protected function getTemplate(string $notificationType, ?string $targetRole = null): ?array
    {
        $baseTemplate = $this->templates[$notificationType] ?? null;
        
        if (!$baseTemplate) {
            return null;
        }

        // Apply role-specific customizations
        if ($targetRole && isset($this->roleCustomizations[$targetRole][$notificationType])) {
            $customization = $this->roleCustomizations[$targetRole][$notificationType];
            $baseTemplate = array_merge($baseTemplate, $customization);
        }

        return $baseTemplate;
    }

    /**
     * Render template with data
     */
    protected function renderTemplate(array $template, array $data, ?string $targetRole = null): string
    {
        $message = '';

        // Header with emoji and title
        $emoji = $template['emoji'] ?? '📢';
        $title = $this->processTemplate($template['title'] ?? 'Notification', $data);
        $message .= "{$emoji} <b>{$title}</b>\n\n";

        // Main content sections
        if (isset($template['sections'])) {
            foreach ($template['sections'] as $section) {
                $sectionContent = $this->renderSection($section, $data, $targetRole);
                if ($sectionContent) {
                    $message .= $sectionContent . "\n";
                }
            }
        }

        // Footer
        if (isset($template['footer'])) {
            $footer = $this->renderSection($template['footer'], $data, $targetRole);
            if ($footer) {
                $message .= "\n" . $footer;
            }
        } else {
            $message .= "\n📅 " . now()->format('d/m/Y H:i:s') . "\n";
            $message .= '🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>';
        }

        return $message;
    }

    /**
     * Render individual section
     */
    protected function renderSection(array $section, array $data, ?string $targetRole = null): string
    {
        $content = '';

        // Check conditional display
        if (isset($section['condition'])) {
            if (!$this->evaluateCondition($section['condition'], $data, $targetRole)) {
                return '';
            }
        }

        // Process section type
        switch ($section['type'] ?? 'text') {
            case 'text':
                $content = $this->processTemplate($section['content'] ?? '', $data);
                break;

            case 'list':
                $content = $this->renderList($section, $data);
                break;

            case 'table':
                $content = $this->renderTable($section, $data);
                break;

            case 'conditional_text':
                $content = $this->renderConditionalText($section, $data, $targetRole);
                break;

            case 'amount':
                $content = $this->renderAmount($section, $data);
                break;

            case 'role_specific':
                $content = $this->renderRoleSpecific($section, $data, $targetRole);
                break;
        }

        return $content;
    }

    /**
     * Process template placeholders
     */
    protected function processTemplate(string $template, array $data): string
    {
        // Replace placeholders like {field_name} with actual data
        return preg_replace_callback('/\{([^}]+)\}/', function ($matches) use ($data) {
            $key = $matches[1];
            
            // Handle nested keys like user.name
            if (str_contains($key, '.')) {
                return $this->getNestedValue($data, $key);
            }
            
            // Handle formatters like {amount|currency}
            if (str_contains($key, '|')) {
                [$field, $formatter] = explode('|', $key, 2);
                $value = $data[$field] ?? '';
                return $this->applyFormatter($value, $formatter);
            }
            
            return $data[$key] ?? '';
        }, $template);
    }

    /**
     * Get nested value from data array
     */
    protected function getNestedValue(array $data, string $key): string
    {
        $keys = explode('.', $key);
        $value = $data;
        
        foreach ($keys as $nestedKey) {
            if (is_array($value) && isset($value[$nestedKey])) {
                $value = $value[$nestedKey];
            } else {
                return '';
            }
        }
        
        return (string) $value;
    }

    /**
     * Apply formatter to value
     */
    protected function applyFormatter($value, string $formatter): string
    {
        return match ($formatter) {
            'currency' => 'Rp ' . number_format($value, 0, ',', '.'),
            'date' => is_string($value) ? $value : (is_object($value) ? $value->format('d/m/Y') : ''),
            'datetime' => is_string($value) ? $value : (is_object($value) ? $value->format('d/m/Y H:i:s') : ''),
            'upper' => strtoupper($value),
            'lower' => strtolower($value),
            'title' => ucwords($value),
            default => (string) $value,
        };
    }

    /**
     * Render list section
     */
    protected function renderList(array $section, array $data): string
    {
        $items = $section['items'] ?? [];
        $content = '';
        
        foreach ($items as $item) {
            $itemContent = $this->processTemplate($item, $data);
            if ($itemContent) {
                $prefix = $section['bullet'] ?? '•';
                $content .= "{$prefix} {$itemContent}\n";
            }
        }
        
        return $content;
    }

    /**
     * Render table section
     */
    protected function renderTable(array $section, array $data): string
    {
        $rows = $section['rows'] ?? [];
        $content = '';
        
        foreach ($rows as $row) {
            $label = $this->processTemplate($row['label'] ?? '', $data);
            $value = $this->processTemplate($row['value'] ?? '', $data);
            
            if ($label && $value) {
                $content .= "{$label}: {$value}\n";
            }
        }
        
        return $content;
    }

    /**
     * Render conditional text
     */
    protected function renderConditionalText(array $section, array $data, ?string $targetRole = null): string
    {
        $conditions = $section['conditions'] ?? [];
        
        foreach ($conditions as $condition) {
            if ($this->evaluateCondition($condition['if'], $data, $targetRole)) {
                return $this->processTemplate($condition['then'], $data);
            }
        }
        
        // Fallback
        if (isset($section['else'])) {
            return $this->processTemplate($section['else'], $data);
        }
        
        return '';
    }

    /**
     * Render amount with formatting
     */
    protected function renderAmount(array $section, array $data): string
    {
        $field = $section['field'] ?? 'amount';
        $amount = $data[$field] ?? 0;
        $label = $section['label'] ?? 'Amount';
        
        return "{$label}: Rp " . number_format($amount, 0, ',', '.');
    }

    /**
     * Render role-specific content
     */
    protected function renderRoleSpecific(array $section, array $data, ?string $targetRole = null): string
    {
        if (!$targetRole) {
            return $section['default'] ?? '';
        }
        
        $roleContent = $section['roles'][$targetRole] ?? $section['default'] ?? '';
        return $this->processTemplate($roleContent, $data);
    }

    /**
     * Evaluate condition
     */
    protected function evaluateCondition(array $condition, array $data, ?string $targetRole = null): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;
        
        $fieldValue = $data[$field] ?? null;
        
        return match ($operator) {
            '=' => $fieldValue == $value,
            '!=' => $fieldValue != $value,
            '>' => is_numeric($fieldValue) && $fieldValue > $value,
            '<' => is_numeric($fieldValue) && $fieldValue < $value,
            '>=' => is_numeric($fieldValue) && $fieldValue >= $value,
            '<=' => is_numeric($fieldValue) && $fieldValue <= $value,
            'in' => is_array($value) && in_array($fieldValue, $value),
            'not_in' => is_array($value) && !in_array($fieldValue, $value),
            'exists' => isset($data[$field]),
            'not_exists' => !isset($data[$field]),
            'role_is' => $targetRole === $value,
            'role_not' => $targetRole !== $value,
            default => false,
        };
    }

    /**
     * Generate fallback message when no template is found
     */
    protected function generateFallbackMessage(string $notificationType, array $data): string
    {
        $enum = TelegramNotificationType::tryFrom($notificationType);
        $title = $enum ? $enum->label() : 'System Notification';
        
        $message = "📢 <b>{$title}</b>\n\n";
        
        // Include basic data if available
        if (isset($data['description'])) {
            $message .= "📝 {$data['description']}\n";
        }
        
        if (isset($data['amount'])) {
            $message .= "💰 Rp " . number_format($data['amount'], 0, ',', '.') . "\n";
        }
        
        $message .= "\n📅 " . now()->format('d/m/Y H:i:s') . "\n";
        $message .= '🏥 <i>Dokterku - SAHABAT MENUJU SEHAT</i>';
        
        return $message;
    }

    /**
     * Load notification templates
     */
    protected function loadTemplates(): void
    {
        $this->templates = [
            TelegramNotificationType::VALIDASI_DISETUJUI->value => [
                'emoji' => '✅',
                'title' => 'Tindakan Disetujui - {dokter_name}',
                'sections' => [
                    [
                        'type' => 'table',
                        'rows' => [
                            ['label' => '🩺 Tindakan', 'value' => '{procedure}'],
                            ['label' => '👤 Pasien', 'value' => '{patient_name}'],
                            ['label' => '💰 Tarif', 'value' => '{amount|currency}'],
                            ['label' => '📅 Tanggal', 'value' => '{date}'],
                            ['label' => '✅ Validator', 'value' => '{validator_name}'],
                        ]
                    ],
                    [
                        'type' => 'conditional_text',
                        'conditions' => [
                            [
                                'if' => ['field' => 'amount', 'operator' => '>', 'value' => 0],
                                'then' => '💵 JASPEL siap dicairkan!'
                            ]
                        ]
                    ]
                ]
            ],

            TelegramNotificationType::TINDAKAN_BARU->value => [
                'emoji' => '🏥',
                'title' => 'Tindakan Medis Baru',
                'sections' => [
                    [
                        'type' => 'table',
                        'rows' => [
                            ['label' => '👤 Pasien', 'value' => '{patient_name}'],
                            ['label' => '🩺 Tindakan', 'value' => '{procedure}'],
                            ['label' => '👨‍⚕️ Dokter', 'value' => '{dokter_name}'],
                            ['label' => '💰 Tarif', 'value' => '{tarif|currency}'],
                            ['label' => '📅 Waktu', 'value' => '{tanggal_tindakan}'],
                        ]
                    ],
                    [
                        'type' => 'conditional_text',
                        'conditions' => [
                            [
                                'if' => ['field' => 'paramedis_name', 'operator' => 'exists'],
                                'then' => '👩‍⚕️ Paramedis: {paramedis_name}'
                            ]
                        ]
                    ]
                ]
            ],

            TelegramNotificationType::JASPEL_DOKTER_READY->value => [
                'emoji' => '💵',
                'title' => 'JASPEL Siap Dicairkan',
                'sections' => [
                    [
                        'type' => 'table',
                        'rows' => [
                            ['label' => '👨‍⚕️ Dokter', 'value' => '{dokter_name}'],
                            ['label' => '💰 Total JASPEL', 'value' => '{total_jaspel|currency}'],
                            ['label' => '📊 Jumlah Tindakan', 'value' => '{total_procedures} tindakan'],
                            ['label' => '📅 Periode', 'value' => '{period}'],
                        ]
                    ],
                    [
                        'type' => 'role_specific',
                        'roles' => [
                            'dokter' => '✨ JASPEL Anda sudah siap untuk dicairkan!',
                            'bendahara' => '📋 Silakan proses pencairan JASPEL',
                            'manajer' => '📊 JASPEL telah dihitung dan siap diproses',
                        ],
                        'default' => '💼 JASPEL telah siap untuk diproses'
                    ]
                ]
            ],

            TelegramNotificationType::EMERGENCY_ALERT->value => [
                'emoji' => '🚨',
                'title' => 'ALERT EMERGENCY',
                'sections' => [
                    [
                        'type' => 'table',
                        'rows' => [
                            ['label' => '⚡ Level', 'value' => '{level}'],
                            ['label' => '📍 Lokasi', 'value' => '{location}'],
                            ['label' => '📝 Deskripsi', 'value' => '{description}'],
                            ['label' => '👤 Dilaporkan', 'value' => '{reporter}'],
                        ]
                    ],
                    [
                        'type' => 'text',
                        'content' => '⚠️ <b>TINDAKAN SEGERA DIPERLUKAN!</b>'
                    ]
                ]
            ],

            TelegramNotificationType::PRESENSI_DOKTER->value => [
                'emoji' => '🩺',
                'title' => 'Presensi Dokter',
                'sections' => [
                    [
                        'type' => 'table',
                        'rows' => [
                            ['label' => '👨‍⚕️ Dokter', 'value' => '{staff_name}'],
                            ['label' => '⏰ Aktivitas', 'value' => '{type}'],
                            ['label' => '🕐 Waktu', 'value' => '{time}'],
                            ['label' => '📅 Shift', 'value' => '{shift}'],
                        ]
                    ]
                ]
            ],

            TelegramNotificationType::PENDAPATAN->value => [
                'emoji' => '💰',
                'title' => 'Pendapatan Baru',
                'sections' => [
                    [
                        'type' => 'table',
                        'rows' => [
                            ['label' => '💰 Jumlah', 'value' => '{amount|currency}'],
                            ['label' => '📝 Deskripsi', 'value' => '{description}'],
                            ['label' => '📅 Tanggal', 'value' => '{date}'],
                            ['label' => '⏰ Shift', 'value' => '{shift}'],
                            ['label' => '👤 Input oleh', 'value' => '{petugas}'],
                        ]
                    ]
                ]
            ],

            TelegramNotificationType::PENGELUARAN->value => [
                'emoji' => '📉',
                'title' => 'Pengeluaran Baru',
                'sections' => [
                    [
                        'type' => 'table',
                        'rows' => [
                            ['label' => '💸 Jumlah', 'value' => '{amount|currency}'],
                            ['label' => '📝 Deskripsi', 'value' => '{description}'],
                            ['label' => '📅 Tanggal', 'value' => '{date}'],
                            ['label' => '⏰ Shift', 'value' => '{shift}'],
                            ['label' => '👤 Input oleh', 'value' => '{petugas}'],
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * Load role-specific customizations
     */
    protected function loadRoleCustomizations(): void
    {
        $this->roleCustomizations = [
            'dokter' => [
                TelegramNotificationType::VALIDASI_DISETUJUI->value => [
                    'title' => 'Tindakan Anda Disetujui! 🎉',
                ],
                TelegramNotificationType::JASPEL_DOKTER_READY->value => [
                    'title' => 'JASPEL Anda Siap! 💰',
                ],
            ],
            'bendahara' => [
                TelegramNotificationType::TINDAKAN_BARU->value => [
                    'title' => 'Tindakan Menunggu Validasi',
                ],
            ],
            'manajer' => [
                TelegramNotificationType::JASPEL_DOKTER_READY->value => [
                    'title' => 'Laporan JASPEL Dokter',
                ],
            ],
        ];
    }

    /**
     * Add custom template
     */
    public function addTemplate(string $notificationType, array $template): void
    {
        $this->templates[$notificationType] = $template;
    }

    /**
     * Add role customization
     */
    public function addRoleCustomization(string $role, string $notificationType, array $customization): void
    {
        $this->roleCustomizations[$role][$notificationType] = $customization;
    }

    /**
     * Get all available templates
     */
    public function getAvailableTemplates(): array
    {
        return array_keys($this->templates);
    }
}