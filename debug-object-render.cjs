#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('🔍 Scanning for potential object rendering issues...\n');

const componentsDir = path.join(__dirname, 'resources/js/components/dokter');
const suspiciousPatterns = [];
const jadwalUsages = [];

// Patterns that might indicate object rendering
const dangerousPatterns = [
  />\s*{[a-zA-Z_]+}\s*</g,  // Simple variable in JSX
  />\s*{[a-zA-Z_]+\s*&&\s*[a-zA-Z_]+}\s*</g,  // Conditional that might return object
  />\s*{[a-zA-Z_]+\s*\?\s*[a-zA-Z_]+\s*:\s*[a-zA-Z_]+}\s*</g,  // Ternary that might return object
  />\s*{[a-zA-Z_]+\s*\|\|\s*[a-zA-Z_]+}\s*</g,  // OR that might return object
  /<>\s*{[a-zA-Z_]+}\s*<\/>/g,  // Variable in Fragment
  /children:\s*[a-zA-Z_]+[^.\[]/g,  // children prop without property access
];

// Function to check if a variable name might be an object
function mightBeObject(varName) {
  const objectNames = ['jadwal', 'data', 'result', 'response', 'item', 'mission', 'shift', 'schedule'];
  return objectNames.some(name => varName.toLowerCase().includes(name));
}

// Scan a file for suspicious patterns
function scanFile(filePath) {
  const content = fs.readFileSync(filePath, 'utf8');
  const lines = content.split('\n');
  const fileName = path.basename(filePath);
  
  // Check for jadwal usage
  if (content.includes('jadwal')) {
    const jadwalMatches = content.match(/jadwal[a-zA-Z_]*/gi) || [];
    jadwalUsages.push({
      file: fileName,
      count: jadwalMatches.length,
      matches: [...new Set(jadwalMatches)]
    });
  }
  
  // Check each dangerous pattern
  dangerousPatterns.forEach((pattern, patternIndex) => {
    let match;
    const regex = new RegExp(pattern.source, pattern.flags);
    
    while ((match = regex.exec(content)) !== null) {
      const lineNumber = content.substring(0, match.index).split('\n').length;
      const line = lines[lineNumber - 1];
      
      // Extract variable name
      const varMatch = match[0].match(/\{([a-zA-Z_]+)/);
      if (varMatch) {
        const varName = varMatch[1];
        
        // Check if it might be an object
        if (mightBeObject(varName)) {
          suspiciousPatterns.push({
            file: fileName,
            line: lineNumber,
            pattern: `Pattern ${patternIndex + 1}`,
            code: line.trim(),
            variable: varName,
            severity: varName.includes('jadwal') ? 'HIGH' : 'MEDIUM'
          });
        }
      }
    }
  });
}

// Scan all TypeScript/JavaScript files
function scanDirectory(dir) {
  const files = fs.readdirSync(dir);
  
  files.forEach(file => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);
    
    if (stat.isDirectory()) {
      scanDirectory(filePath);
    } else if (file.endsWith('.tsx') || file.endsWith('.jsx')) {
      scanFile(filePath);
    }
  });
}

// Start scanning
scanDirectory(componentsDir);

// Report results
console.log('📊 SCAN RESULTS\n');
console.log('=====================================\n');

console.log(`Found ${suspiciousPatterns.length} suspicious patterns\n`);

// Group by severity
const highSeverity = suspiciousPatterns.filter(p => p.severity === 'HIGH');
const mediumSeverity = suspiciousPatterns.filter(p => p.severity === 'MEDIUM');

if (highSeverity.length > 0) {
  console.log('🚨 HIGH SEVERITY ISSUES (likely jadwal objects):');
  console.log('------------------------------------------------');
  highSeverity.forEach(issue => {
    console.log(`\n📁 ${issue.file}:${issue.line}`);
    console.log(`   Variable: ${issue.variable}`);
    console.log(`   Code: ${issue.code}`);
  });
}

if (mediumSeverity.length > 0) {
  console.log('\n\n⚠️  MEDIUM SEVERITY ISSUES (might be objects):');
  console.log('-----------------------------------------------');
  mediumSeverity.slice(0, 10).forEach(issue => {
    console.log(`\n📁 ${issue.file}:${issue.line}`);
    console.log(`   Variable: ${issue.variable}`);
    console.log(`   Code: ${issue.code}`);
  });
  
  if (mediumSeverity.length > 10) {
    console.log(`\n... and ${mediumSeverity.length - 10} more medium severity issues`);
  }
}

console.log('\n\n📈 JADWAL USAGE STATISTICS:');
console.log('---------------------------');
jadwalUsages.sort((a, b) => b.count - a.count).forEach(usage => {
  console.log(`\n${usage.file}: ${usage.count} references`);
  console.log(`   Variables: ${usage.matches.join(', ')}`);
});

// Recommendations
console.log('\n\n💡 RECOMMENDATIONS:');
console.log('-------------------');
console.log('1. Check all HIGH severity issues first');
console.log('2. Look for patterns where jadwal objects might be rendered without property access');
console.log('3. Common fixes:');
console.log('   - Change {jadwal} to {jadwal.property}');
console.log('   - Change {condition && jadwal} to {condition && <Component jadwal={jadwal} />}');
console.log('   - Change {jadwal || default} to {jadwal?.property || default}');
console.log('\n✅ Scan complete!');

// Save detailed report
const report = {
  timestamp: new Date().toISOString(),
  suspiciousPatterns,
  jadwalUsages,
  summary: {
    total: suspiciousPatterns.length,
    high: highSeverity.length,
    medium: mediumSeverity.length
  }
};

fs.writeFileSync('debug-report.json', JSON.stringify(report, null, 2));
console.log('\n📄 Detailed report saved to debug-report.json');