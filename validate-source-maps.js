#!/usr/bin/env node

/**
 * Source Map Validation & Debugging Tool
 * 
 * Validates source map generation and helps diagnose debugging issues.
 * Usage: node validate-source-maps.js
 */

import { readFileSync, readdirSync, statSync } from 'fs';
import { join, extname } from 'path';

const BUILD_DIR = './public/build/assets/js';
const colors = {
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    reset: '\x1b[0m',
    bold: '\x1b[1m',
};

function log(color, message) {
    console.log(color + message + colors.reset);
}

function validateSourceMaps() {
    log(colors.blue + colors.bold, '\n🔍 VITE SOURCE MAP VALIDATION REPORT');
    log(colors.blue, '=' .repeat(50));
    
    try {
        const files = readdirSync(BUILD_DIR);
        const jsFiles = files.filter(file => extname(file) === '.js');
        const mapFiles = files.filter(file => file.endsWith('.js.map'));
        
        log(colors.blue, `\n📊 BUILD DIRECTORY ANALYSIS:`);
        log(colors.reset, `   JavaScript files: ${jsFiles.length}`);
        log(colors.reset, `   Source map files: ${mapFiles.length}`);
        log(colors.reset, `   Build directory: ${BUILD_DIR}`);
        
        let hasErrors = false;
        let sourceMapIssues = [];
        let validSourceMaps = [];
        
        jsFiles.forEach(jsFile => {
            const jsPath = join(BUILD_DIR, jsFile);
            const mapPath = join(BUILD_DIR, jsFile + '.map');
            
            try {
                const jsContent = readFileSync(jsPath, 'utf8');
                const lastLine = jsContent.split('\n').pop().trim();
                
                // Check for source map reference
                const hasSourceMapComment = lastLine.includes('sourceMappingURL');
                const sourceMapExists = mapFiles.includes(jsFile + '.map');
                
                const analysis = {
                    file: jsFile,
                    size: statSync(jsPath).size,
                    hasSourceMapComment,
                    sourceMapExists,
                    sourceMapReference: hasSourceMapComment ? lastLine : null,
                };
                
                if (hasSourceMapComment && sourceMapExists) {
                    // Validate source map content
                    try {
                        const mapContent = readFileSync(mapPath, 'utf8');
                        const sourceMap = JSON.parse(mapContent);
                        
                        analysis.sourceMapValid = true;
                        analysis.sourceMapInfo = {
                            version: sourceMap.version,
                            sources: sourceMap.sources ? sourceMap.sources.length : 0,
                            mappings: sourceMap.mappings ? sourceMap.mappings.length : 0,
                            names: sourceMap.names ? sourceMap.names.length : 0,
                        };
                        
                        validSourceMaps.push(analysis);
                        log(colors.green, `✅ ${jsFile}`);
                        log(colors.reset, `   → Source map: ${jsFile}.map`);
                        log(colors.reset, `   → Sources: ${analysis.sourceMapInfo.sources}`);
                        
                    } catch (mapError) {
                        analysis.sourceMapValid = false;
                        analysis.mapError = mapError.message;
                        sourceMapIssues.push(analysis);
                        hasErrors = true;
                        log(colors.red, `❌ ${jsFile} - Invalid source map`);
                        log(colors.red, `   → Error: ${mapError.message}`);
                    }
                    
                } else {
                    sourceMapIssues.push(analysis);
                    hasErrors = true;
                    
                    if (!hasSourceMapComment) {
                        log(colors.red, `❌ ${jsFile} - No source map comment`);
                    }
                    if (!sourceMapExists) {
                        log(colors.red, `❌ ${jsFile} - Source map file missing`);
                    }
                }
                
            } catch (error) {
                sourceMapIssues.push({
                    file: jsFile,
                    error: error.message
                });
                hasErrors = true;
                log(colors.red, `❌ ${jsFile} - Read error: ${error.message}`);
            }
        });
        
        // Summary Report
        log(colors.blue + colors.bold, '\n📋 SUMMARY REPORT:');
        log(colors.blue, '=' .repeat(30));
        
        if (validSourceMaps.length > 0) {
            log(colors.green, `✅ Valid source maps: ${validSourceMaps.length}`);
        }
        
        if (sourceMapIssues.length > 0) {
            log(colors.red, `❌ Source map issues: ${sourceMapIssues.length}`);
        }
        
        // Recommendations
        log(colors.blue + colors.bold, '\n🔧 RECOMMENDATIONS:');
        log(colors.blue, '=' .repeat(25));
        
        if (hasErrors) {
            log(colors.yellow, '1. Run development build to generate source maps:');
            log(colors.reset, '   npm run build:dev');
            
            log(colors.yellow, '\n2. Use debug configuration for detailed analysis:');
            log(colors.reset, '   NODE_ENV=development vite build --config debug-vite-config.js');
            
            log(colors.yellow, '\n3. Check Vite configuration:');
            log(colors.reset, '   → Ensure sourcemap: true in build options');
            log(colors.reset, '   → Verify esbuild.sourcemap: true');
            log(colors.reset, '   → Check rollupOptions.output.sourcemapFileNames');
            
            log(colors.yellow, '\n4. Clear build cache and rebuild:');
            log(colors.reset, '   npm run clean-build');
        } else {
            log(colors.green, '✅ All source maps are properly configured!');
            log(colors.green, '✅ Debugging should work correctly in browsers.');
        }
        
        // TDZ (Temporal Dead Zone) Analysis
        log(colors.blue + colors.bold, '\n🧬 TDZ SAFETY ANALYSIS:');
        log(colors.blue, '=' .repeat(30));
        
        let tdzIssues = 0;
        validSourceMaps.forEach(analysis => {
            const jsPath = join(BUILD_DIR, analysis.file);
            const jsContent = readFileSync(jsPath, 'utf8');
            
            // Check for TDZ-unsafe patterns
            const hoistingIssues = [];
            if (jsContent.includes('const ') && jsContent.includes('var ')) {
                hoistingIssues.push('Mixed const/var declarations');
            }
            if (jsContent.match(/\bclass\s+\w+\s+extends\s+\w+\s+{[^}]*constructor\s*\([^)]*\)\s*{[^}]*super\s*\(/)) {
                hoistingIssues.push('Class constructor patterns detected');
            }
            
            if (hoistingIssues.length > 0) {
                log(colors.yellow, `⚠️  ${analysis.file}:`);
                hoistingIssues.forEach(issue => {
                    log(colors.reset, `   → ${issue}`);
                });
                tdzIssues++;
            }
        });
        
        if (tdzIssues === 0) {
            log(colors.green, '✅ No TDZ issues detected in build output');
        }
        
        // Check for esbuild usage
        const usesEsbuild = validSourceMaps.some(analysis => {
            const jsPath = join(BUILD_DIR, analysis.file);
            const jsContent = readFileSync(jsPath, 'utf8');
            return jsContent.includes('esbuild') || !jsContent.includes('var ');
        });
        
        if (usesEsbuild) {
            log(colors.green, '✅ TDZ-safe minification detected (using esbuild)');
        }
        
        log(colors.blue, '\n' + '=' .repeat(50));
        log(colors.blue, '🎯 Validation complete!');
        
        process.exit(hasErrors ? 1 : 0);
        
    } catch (error) {
        log(colors.red, `❌ Validation failed: ${error.message}`);
        process.exit(1);
    }
}

validateSourceMaps();