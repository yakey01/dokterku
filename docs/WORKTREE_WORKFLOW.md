# Dokterku Git Worktree Workflow

## Overview
This document describes the Git worktree workflow for the Dokterku healthcare management system. The worktree structure allows for parallel development on multiple features while maintaining a clean, organized codebase.

## Worktree Structure

```
/Users/kym/Herd/
├── Dokterku/                    # Main worktree (production-ready main branch)
├── Dokterku-develop/            # Development integration worktree
├── Dokterku-staging/            # Staging/testing worktree
├── Dokterku-hotfix/             # Emergency hotfix worktree
└── Dokterku-feature-*/          # Feature development worktrees (created as needed)
```

## Branch Strategy

### Main Branches
- **main**: Production-ready code only. Direct commits prohibited.
- **develop**: Integration branch for features. All features merge here first.
- **staging**: Pre-production testing. Mirrors production environment.
- **hotfix**: Emergency fixes that bypass normal workflow.

### Feature Branches
- **feature/***: Individual feature development
- **release/***: Release preparation and stabilization
- **bugfix/***: Non-critical bug fixes

## Workflow Commands

### Helper Script Usage
Use the provided helper script for common operations:

```bash
# List all worktrees
./scripts/worktree-helper.sh list

# Create a new feature worktree
./scripts/worktree-helper.sh feature attendance-system

# Switch to a worktree
./scripts/worktree-helper.sh switch develop

# Sync all worktrees
./scripts/worktree-helper.sh sync

# Clean up merged branches
./scripts/worktree-helper.sh cleanup
```

### Manual Git Commands

#### Creating Feature Worktrees
```bash
# Create new feature branch and worktree
git worktree add ../Dokterku-feature-new-feature -b feature/new-feature develop
cd ../Dokterku-feature-new-feature
```

#### Working with Features
```bash
# Start working on feature
cd ../Dokterku-feature-attendance-system
git checkout feature/attendance-system

# Make changes and commit
git add .
git commit -m "feat: implement attendance tracking"

# Push feature branch
git push origin feature/attendance-system
```

#### Merging to Develop
```bash
# Switch to develop worktree
cd ../Dokterku-develop
git checkout develop
git pull origin develop

# Merge feature
git merge feature/attendance-system
git push origin develop
```

#### Cleaning Up
```bash
# Remove feature worktree when done
git worktree remove ../Dokterku-feature-attendance-system

# Delete feature branch (if merged)
git branch -d feature/attendance-system
git push origin --delete feature/attendance-system
```

## Development Workflow

### 1. Starting New Feature
1. Create feature worktree from develop branch
2. Switch to feature worktree directory
3. Implement feature with regular commits
4. Push feature branch to GitHub

### 2. Code Review Process
1. Create Pull Request from feature branch to develop
2. Request code review from team members
3. Address review feedback
4. Merge PR once approved

### 3. Integration Testing
1. Switch to develop worktree
2. Pull latest changes
3. Run full test suite
4. Deploy to staging environment

### 4. Production Release
1. Create release branch from develop
2. Final testing and bug fixes
3. Merge to main branch
4. Tag release version
5. Deploy to production

### 5. Hotfix Process
1. Create hotfix worktree from main
2. Implement critical fix
3. Test thoroughly
4. Merge to both main and develop
5. Deploy immediately

## Best Practices

### Worktree Management
- Keep main worktree clean for production code
- Use feature worktrees for all development
- Remove worktrees after feature completion
- Sync regularly with remote repository

### Commit Guidelines
- Use conventional commit messages
- Make atomic commits with single responsibility
- Include tests with feature implementations
- Update documentation as needed

### Code Quality
- Run linters and tests before commits
- Follow Laravel and React coding standards
- Ensure mobile-first responsive design
- Maintain security best practices

### Branch Protection
- Main branch requires pull request reviews
- Status checks must pass before merging
- No direct commits to main or develop
- Enforce linear history where possible

## Troubleshooting

### Common Issues

#### Worktree Already Exists
```bash
# Remove existing worktree first
git worktree remove ../Dokterku-feature-name
# Then recreate
git worktree add ../Dokterku-feature-name -b feature/name develop
```

#### Branch Already Exists
```bash
# Delete existing branch
git branch -D feature/name
# Or checkout existing branch
git worktree add ../Dokterku-feature-name feature/name
```

#### Sync Issues
```bash
# Force sync all worktrees
git fetch origin
git worktree list
# Manually sync each worktree if needed
```

### Recovery Procedures

#### Lost Worktree
```bash
# List all worktrees
git worktree list
# Remove broken reference
git worktree prune
# Recreate if needed
git worktree add ../Dokterku-feature-name feature/name
```

#### Corrupted Branch
```bash
# Reset to last known good state
git reset --hard origin/feature/name
# Or create new branch from develop
git checkout -b feature/name-new develop
```

## Security Considerations

### File Permissions
- Scripts are executable but not world-writable
- Sensitive files remain gitignored
- No credentials in worktree directories

### Access Control
- Each worktree maintains same access controls
- GitHub branch protection rules apply
- Code review requirements enforced

## Integration with IDEs

### VS Code
- Each worktree can have separate VS Code workspace
- Use workspace files for project-specific settings
- Extensions and configurations sync across worktrees

### PhpStorm/WebStorm
- Open each worktree as separate project
- Maintain consistent coding standards
- Share run configurations across projects

## Monitoring and Metrics

### Workflow Efficiency
- Track feature development time
- Monitor merge conflicts frequency
- Measure code review turnaround

### Quality Metrics
- Test coverage per feature
- Bug reports per release
- Performance impact assessment

---

**Note**: This workflow is designed specifically for the Dokterku healthcare management system and should be adapted as the project evolves.