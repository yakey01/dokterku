<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WorkLocation;
use Illuminate\Support\Facades\Schema;

// Check if the tolerance columns exist in the work_locations table
$columns = Schema::getColumnListing('work_locations');
echo "Columns in work_locations table:\n";
foreach ($columns as $column) {
    if (strpos($column, 'tolerance') !== false || 
        strpos($column, 'checkin') !== false || 
        strpos($column, 'early') !== false || 
        strpos($column, 'late') !== false) {
        echo "  - $column\n";
    }
}

// Check if we need to add the missing column
if (!in_array('checkin_before_shift_minutes', $columns)) {
    echo "\n⚠️ Column 'checkin_before_shift_minutes' does not exist in work_locations table.\n";
    echo "This column is needed for check-in tolerance to work properly.\n";
    
    // Create a migration to add the column
    $migrationContent = '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table("work_locations", function (Blueprint $table) {
            if (!Schema::hasColumn("work_locations", "checkin_before_shift_minutes")) {
                $table->integer("checkin_before_shift_minutes")->nullable()->default(30)->after("radius_meters");
            }
        });
    }

    public function down()
    {
        Schema::table("work_locations", function (Blueprint $table) {
            $table->dropColumn("checkin_before_shift_minutes");
        });
    }
};';
    
    $migrationFile = database_path('migrations/' . date('Y_m_d_His') . '_add_checkin_before_shift_minutes_to_work_locations.php');
    file_put_contents($migrationFile, $migrationContent);
    echo "Created migration: $migrationFile\n";
    echo "Run 'php artisan migrate' to apply this migration.\n";
}

// Update the work location with tolerance values
$workLocation = WorkLocation::first();
if ($workLocation) {
    echo "\nUpdating tolerance settings for: {$workLocation->name}\n";
    
    // Update existing columns
    if (in_array('late_tolerance_minutes', $columns)) {
        $workLocation->late_tolerance_minutes = 15;
        echo "  - Set late_tolerance_minutes to 15\n";
    }
    
    if (in_array('checkin_before_shift_minutes', $columns)) {
        $workLocation->checkin_before_shift_minutes = 30;
        echo "  - Set checkin_before_shift_minutes to 30\n";
    }
    
    if (in_array('early_departure_tolerance_minutes', $columns)) {
        $workLocation->early_departure_tolerance_minutes = 15;
        echo "  - Set early_departure_tolerance_minutes to 15\n";
    }
    
    // Also set the tolerance_settings JSON field
    $workLocation->tolerance_settings = [
        'late_tolerance_minutes' => 15,
        'checkin_before_shift_minutes' => 30,
        'early_departure_tolerance_minutes' => 15,
        'break_time_minutes' => 60,
        'overtime_threshold_minutes' => 480
    ];
    
    $workLocation->save();
    echo "Work location tolerance settings updated successfully!\n";
    
    // Display the updated settings
    echo "\nCurrent tolerance settings:\n";
    echo "  - Check-in allowed: 30 minutes before shift start\n";
    echo "  - Late tolerance: 15 minutes after shift start\n";
    echo "  - Early checkout: 15 minutes before shift end\n";
}