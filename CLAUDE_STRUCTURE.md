# 🚀 Claude Code Pro Tip: Subdirectory CLAUDE.md Files

## Why This Matters
Instead of one giant CLAUDE.md file with hundreds of rules, each directory now has its own context-specific guidelines. This makes Claude Code:
- 🎯 More precise and context-aware
- 🧠 Less "dumb" about specific parts of your codebase  
- ⚡ Faster at understanding what to do in each directory
- 📚 Easier to maintain and update

## Directory-Specific Guidelines

### 📁 `/app/CLAUDE.md`
- Laravel application architecture
- Model, Controller, Service patterns
- Database best practices
- Security requirements

### 📁 `/app/Filament/CLAUDE.md`
- **CRITICAL**: Resource visibility methods (the fix for missing navigation!)
- Panel-specific configurations
- Form and table patterns
- Common Filament issues & solutions

### 📁 `/resources/js/CLAUDE.md`
- React/TypeScript patterns
- Component structure
- API integration
- Performance optimization
- Mobile-first design

### 📁 `/resources/views/CLAUDE.md`
- Blade template best practices
- Security (escaping, CSRF)
- Component usage
- Mobile view patterns

### 📁 `/routes/CLAUDE.md`
- Route naming conventions
- Middleware patterns
- API versioning
- Security best practices

### 📁 `/database/CLAUDE.md`
- Migration patterns
- Seeder best practices
- Factory patterns
- Performance optimization
- SQLite limitations

### 📁 `/tests/CLAUDE.md`
- PHPUnit patterns
- Filament testing
- Mocking & fakes
- Coverage requirements

### 📁 `/public/CLAUDE.md`
- Security rules (what NOT to store)
- Asset management
- Storage symlinks
- Deployment checklist

## How Claude Code Uses These

When you ask Claude Code to work in a specific directory, it will:
1. First check for a local `CLAUDE.md` in that directory
2. Apply those specific rules and patterns
3. Fall back to root `CLAUDE.md` for general guidelines

## Example Benefits

### Before (One Giant CLAUDE.md):
```
Claude: *Might forget that Filament resources need shouldRegisterNavigation()*
Claude: *Might use wrong import patterns in React*
Claude: *Might put sensitive files in /public*
```

### After (Subdirectory CLAUDE.md):
```
Claude: ✅ Knows Filament resources MUST have visibility methods
Claude: ✅ Uses correct React/TypeScript patterns automatically
Claude: ✅ Never puts sensitive files in /public
```

## Pro Tips

1. **Keep them focused**: Each CLAUDE.md should only contain rules relevant to that directory
2. **Include examples**: Show good and bad patterns
3. **List common issues**: Help Claude avoid known pitfalls
4. **Update regularly**: As you discover new patterns or issues, update the relevant CLAUDE.md

## Quick Setup for Other Projects

```bash
# Create CLAUDE.md files in your key directories
touch app/CLAUDE.md
touch resources/js/CLAUDE.md
touch resources/views/CLAUDE.md
touch database/CLAUDE.md
touch tests/CLAUDE.md

# Add directory-specific rules to each file
```

## Results You'll See

- 🎯 More accurate code generation
- 🐛 Fewer bugs and mistakes
- 🚀 Faster development
- 📚 Better documentation
- 🧠 Smarter Claude Code responses

---

*This approach transforms Claude Code from a general-purpose assistant into a specialized expert for each part of your codebase!*