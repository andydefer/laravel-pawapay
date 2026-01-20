# ===================================================
# PHP/Laravel Package Development Makefile
# ===================================================
# This Makefile provides utilities for package development,
# including code quality checks, version management, and file tracking.
# ===================================================

# ---------------------------------------------------
# Tool Executables
# ---------------------------------------------------
PINT = ./vendor/bin/pint
PHPSTAN = ./vendor/bin/phpstan
RECTOR = ./vendor/bin/rector
PSALM = ./vendor/bin/psalm

# ---------------------------------------------------
# Source Configuration
# ---------------------------------------------------
SOURCE_DIRS = src config database tests
IGNORED_FILES = CHANGED_FILES.md FILES_CHECKLIST.md psalm.md phpstan.md pint-test.md Makefile pint.md .gitkeep

# ---------------------------------------------------
# Version Control Operations
# ---------------------------------------------------

.PHONY: enable-cache
enable-cache:
	@sed -i.bak -E "s/('with_cache'\s*=>\s*)false/\1true/" config/roster.php
	@echo "✅ Cache enabled in roster.php"

.PHONY: pre-commit
pre-commit:
	@echo "🔍 Running pre-commit checks..."
	@rm -f all.txt diff.txt
	@make lint-all-fix-md
	#@make test
	@echo "✅ Pre-commit checks passed"

.PHONY: toggle-prompts
toggle-prompts:
	@if grep -q '^prompts/' .gitignore; then \
		# Il est décommenté → on commente \
		sed -i.bak 's/^prompts\//#prompts\//' .gitignore; \
		echo "✅ prompts/ commented in .gitignore"; \
	else \
		# Il est commenté → on décommente \
		sed -i.bak 's/^#\s*prompts\//prompts\//' .gitignore; \
		echo "✅ prompts/ uncommented in .gitignore"; \
	fi

.PHONY: git-commit-push
git-commit-push: pre-commit
	@make toggle-prompts
	@read -p "Enter commit message: " commit_message; \
	if [ -z "$$commit_message" ]; then \
		echo "❌ Error: Commit message cannot be empty"; \
		exit 1; \
	fi; \
	git add .; \
	git commit -m "$$commit_message"; \
	git push
	@make toggle-prompts


.PHONY: git-tag
git-tag:
	@bash -c '\
	read -p "Tag type (major/minor/patch): " tag_type; \
	last_tag=$$(git tag --sort=-v:refname | head -n 1); \
	if [ -z "$$last_tag" ]; then last_tag="0.0.0"; fi; \
	major=$$(echo $$last_tag | cut -d. -f1); \
	minor=$$(echo $$last_tag | cut -d. -f2); \
	patch=$$(echo $$last_tag | cut -d. -f3); \
	case "$$tag_type" in \
		major) major=$$((major + 1)); minor=0; patch=0;; \
		minor) minor=$$((minor + 1)); patch=0;; \
		patch) patch=$$((patch + 1));; \
		*) echo "❌ Invalid tag type: $$tag_type"; exit 1;; \
	esac; \
	new_tag="$$major.$$minor.$$patch"; \
	git tag -a "$$new_tag" -m "Release $$new_tag"; \
	git push origin "$$new_tag"; \
	echo "✅ Released new tag: $$new_tag"; \
	'
.PHONY: generate-ai-diff
generate-ai-diff:
	@read -p "📁 Enter directory/path(s) to include in the diff (space-separated, leave empty for all changes): " DIR_PATHS; \
	if [ -z "$$DIR_PATHS" ]; then \
		echo "📝 Generating git diff for ALL changes into diff.txt..."; \
		echo "Tu es un expert en revue de code et en conventions de commits (Conventional Commits)." > diff.txt; \
		echo "" >> diff.txt; \
		echo "À partir du diff Git ci-dessous, fais les choses suivantes :" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "1. Propose un nom de commit clair et concis en anglais" >> diff.txt; \
		echo "   avec le format <type>(<scope>): <description>," >> diff.txt; \
		echo "   en respectant les Conventional Commits" >> diff.txt; \
		echo "   (ex: feat:, fix:, refactor:, test:, chore:, docs:)." >> diff.txt; \
		echo "" >> diff.txt; \
		echo "2. Rédige un résumé du travail effectué en quelques phrases," >> diff.txt; \
		echo "   orienté métier et technique." >> diff.txt; \
		echo "" >> diff.txt; \
		echo "3. Donne une liste d'exemples concrets de changements, en t'appuyant sur le diff :" >> diff.txt; \
		echo "   - méthodes ajoutées, modifiées ou supprimées" >> diff.txt; \
		echo "   - responsabilités déplacées ou clarifiées" >> diff.txt; \
		echo "   - améliorations de validation, de logique ou de structure" >> diff.txt; \
		echo "   - impacts fonctionnels éventuels" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "Contraintes :" >> diff.txt; \
		echo "   - Ne décris que ce qui est réellement visible dans le diff" >> diff.txt; \
		echo "   - Sois précis, factuel et structuré" >> diff.txt; \
		echo "   - Évite les suppositions" >> diff.txt; \
		echo "   - Utilise un ton professionnel" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "4. SI et SEULEMENT SI les changements sont cassants (breaking changes) :" >> diff.txt; \
		echo "   - Génère une entrée de CHANGELOG conforme à Keep a Changelog et SemVer." >> diff.txt; \
		echo "   - Le changelog doit apparaître APRES les recommandations ci-dessus." >> diff.txt; \
		echo "   - Utilise STRICTEMENT la structure suivante :" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "     ## [X.0.0] - YYYY-MM-DD" >> diff.txt; \
		echo "     ### Changed" >> diff.txt; \
		echo "     - Description claire du changement cassant" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "     ### Removed (si applicable)" >> diff.txt; \
		echo "     - API, méthode ou comportement supprimé" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "     ### Security (si applicable)" >> diff.txt; \
		echo "     - Impact sécurité lié au changement" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "   - Ne génère PAS de changelog si aucun breaking change n'est détecté." >> diff.txt; \
		echo "   - N'invente PAS de version." >> diff.txt; \
		echo "" >> diff.txt; \
		echo "Voici le diff :" >> diff.txt; \
		echo "" >> diff.txt; \
		git diff HEAD -- . ':!*.phpunit.result.cache' ':!diff.txt' >> diff.txt; \
		echo "✅ Clean diff.txt generated successfully for ALL changes (excluded test cache files)"; \
	else \
		echo "📝 Generating clean git diff for paths: $${DIR_PATHS} into diff.txt..."; \
		echo "Tu es un expert en revue de code et en conventions de commits (Conventional Commits)." > diff.txt; \
		echo "" >> diff.txt; \
		echo "À partir du diff Git ci-dessous, fais les choses suivantes :" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "1. Propose un nom de commit clair et concis en anglais" >> diff.txt; \
		echo "   avec le format <type>(<scope>): <description>," >> diff.txt; \
		echo "   en respectant les Conventional Commits" >> diff.txt; \
		echo "   (ex: feat:, fix:, refactor:, test:, chore:, docs:)." >> diff.txt; \
		echo "" >> diff.txt; \
		echo "2. Rédige un résumé du travail effectué en quelques phrases," >> diff.txt; \
		echo "   orienté métier et technique." >> diff.txt; \
		echo "" >> diff.txt; \
		echo "3. Donne une liste d'exemples concrets de changements, en t'appuyant sur le diff :" >> diff.txt; \
		echo "   - méthodes ajoutées, modifiées ou supprimées" >> diff.txt; \
		echo "   - responsabilités déplacées ou clarifiées" >> diff.txt; \
		echo "   - améliorations de validation, de logique ou de structure" >> diff.txt; \
		echo "   - impacts fonctionnels éventuels" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "Contraintes :" >> diff.txt; \
		echo "   - Ne décris que ce qui est réellement visible dans le diff" >> diff.txt; \
		echo "   - Sois précis, factuel et structuré" >> diff.txt; \
		echo "   - Évite les suppositions" >> diff.txt; \
		echo "   - Utilise un ton professionnel" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "4. SI et SEULEMENT SI les changements sont cassants (breaking changes) :" >> diff.txt; \
		echo "   - Génère une entrée de CHANGELOG conforme à Keep a Changelog et SemVer." >> diff.txt; \
		echo "   - Le changelog doit apparaître APRES les recommandations ci-dessus." >> diff.txt; \
		echo "   - Utilise STRICTEMENT la structure suivante :" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "     ## [X.0.0] - YYYY-MM-DD" >> diff.txt; \
		echo "     ### Changed" >> diff.txt; \
		echo "     - Description claire du changement cassant" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "     ### Removed (si applicable)" >> diff.txt; \
		echo "     - API, méthode ou comportement supprimé" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "     ### Security (si applicable)" >> diff.txt; \
		echo "     - Impact sécurité lié au changement" >> diff.txt; \
		echo "" >> diff.txt; \
		echo "   - Ne génère PAS de changelog si aucun breaking change n'est détecté." >> diff.txt; \
		echo "   - N'invente PAS de version." >> diff.txt; \
		echo "" >> diff.txt; \
		echo "Voici le diff :" >> diff.txt; \
		echo "" >> diff.txt; \
		git diff HEAD -- $$DIR_PATHS ':!*.phpunit.result.cache' ':!diff.txt' >> diff.txt; \
		echo "✅ Clean diff.txt generated successfully for paths: $${DIR_PATHS} (excluded test cache files)"; \
	fi

.PHONY: git-tag-republish
git-tag-republish:
	@bash -c '\
	last_tag=$$(git tag --sort=-v:refname | head -n 1); \
	if [ -z "$$last_tag" ]; then echo "❌ No tags found!"; exit 1; fi; \
	echo "Republishing last tag: $$last_tag"; \
	git push origin "$$last_tag" --force; \
	echo "✅ Tag $$last_tag republished"; \
	'

# ---------------------------------------------------
# File Management Operations
# ---------------------------------------------------

.PHONY: update-checklist
update-checklist:
	@echo "📋 Updating FILES_CHECKLIST.md..."
	@if [ -f FILES_CHECKLIST.md ]; then \
		grep -E '^[0-9]+\. .* \[[ xX]\]$$' FILES_CHECKLIST.md > .existing_checklist.tmp; \
		awk -F' ' '{ \
			file_path=""; \
			for(i=2;i<NF;i++) { \
				if(i>2) file_path=file_path" "; \
				file_path=file_path$$i; \
			} \
			checkmark_state=$$NF; \
			print file_path " " checkmark_state \
		}' .existing_checklist.tmp > .existing_files.tmp; \
	else \
		touch .existing_files.tmp; \
		touch FILES_CHECKLIST.md; \
	fi; \
	echo "# Project File Checklist" > FILES_CHECKLIST.md; \
	echo "*Last updated: $$(date)*" >> FILES_CHECKLIST.md; \
	echo "" >> FILES_CHECKLIST.md; \
	echo "## Previously Checked Files" >> FILES_CHECKLIST.md; \
	file_count=1; \
	grep '\[x\]' .existing_files.tmp | sort | uniq | while read -r line; do \
		file_path=$$(echo "$$line" | awk '{$$NF=""; print $$0}' | sed 's/ $$//'); \
		echo "$$file_count. $$file_path [x]" >> FILES_CHECKLIST.md; \
		file_count=$$((file_count + 1)); \
	done; \
	previously_checked_files=$$(grep '\[x\]' .existing_files.tmp | awk '{$$NF=""; print $$0}' | sed 's/ $$//'); \
	echo "" >> FILES_CHECKLIST.md; \
	echo "## Other Files" >> FILES_CHECKLIST.md; \
	file_count=1; \
	find $(SOURCE_DIRS) -type f | sort | while read -r file_path; do \
		if ! echo "$$previously_checked_files" | grep -Fxq "$$file_path" 2>/dev/null; then \
			echo "$$file_count. $$file_path [ ]" >> FILES_CHECKLIST.md; \
			file_count=$$((file_count + 1)); \
		fi; \
	done; \
	rm -f .existing_checklist.tmp .existing_files.tmp; \
	echo "✅ FILES_CHECKLIST.md updated successfully"

.PHONY: list-modified-files
list-modified-files:
	@echo "📝 Updating CHANGED_FILES.md..."
	@previously_checked_files=$$(grep -E '^[0-9]+\. .* \[[xX]\]' FILES_CHECKLIST.md | sed 's/^[0-9]\+\. //' | sed 's/ *\[[xX]\]$$//'); \
	modified_file_count=0; \
	all_files=$$( (git diff --name-only; git ls-files --others --exclude-standard) | sort -u ); \
	echo "# Changed and Untracked Files" > CHANGED_FILES.md; \
	echo "*Updated: $$(date)*" >> CHANGED_FILES.md; \
	echo "" >> CHANGED_FILES.md; \
	echo "## Files to Review (modifications on checked files)" >> CHANGED_FILES.md; \
	for file_path in $$all_files; do \
		if echo "$$previously_checked_files" | grep -Fxq "$$file_path"; then \
			modified_file_count=$$((modified_file_count + 1)); \
			echo "$$modified_file_count. $$file_path [x]" >> CHANGED_FILES.md; \
		fi; \
	done; \
	if [ $$modified_file_count -eq 0 ]; then \
		echo "*(No modified files in this category)*" >> CHANGED_FILES.md; \
	fi; \
	echo "" >> CHANGED_FILES.md; \
	echo "## Other Modified Files" >> CHANGED_FILES.md; \
	modified_file_count=0; \
	for file_path in $$all_files; do \
		should_skip_file=0; \
		for ignored_file in $$(echo -e "$(IGNORED_FILES)"); do \
			if [ "$$file_path" = "$$ignored_file" ]; then should_skip_file=1; break; fi; \
		done; \
		if [ $$should_skip_file -eq 0 ] && ! echo "$$previously_checked_files" | grep -Fxq "$$file_path"; then \
			modified_file_count=$$((modified_file_count + 1)); \
			echo "$$modified_file_count. $$file_path [ ]" >> CHANGED_FILES.md; \
		fi; \
	done; \
	if [ $$modified_file_count -eq 0 ]; then \
		echo "*(No modified files in this category)*" >> CHANGED_FILES.md; \
	fi; \
	echo "✅ CHANGED_FILES.md updated successfully"

.PHONY: update-all
update-all: update-checklist list-modified-files
	@echo "✅ All file management updates completed"

.PHONY: concat-all
concat-all:
	@read -p "📁 Enter the source directory path to scan (leave empty for default './app ./database ./routes'): " SOURCE_PATH; \
	if [ -z "$$SOURCE_PATH" ]; then \
		SOURCE_DIRS="./app ./database ./routes"; \
		echo "🔗 Concatenating all PHP files from default directories: $${SOURCE_DIRS} into all.txt..."; \
	else \
		SOURCE_DIRS="$$SOURCE_PATH"; \
		echo "🔗 Concatenating all PHP files from directory: $${SOURCE_DIRS} into all.txt..."; \
	fi; \
	find $${SOURCE_DIRS} -type f -name "*.php" -exec sh -c 'echo ""; echo "// ==== {} ==="; echo ""; cat {}' \; > all.txt; \
	echo "✅ File all.txt generated successfully from: $${SOURCE_DIRS}"
# ---------------------------------------------------
# Testing
# ---------------------------------------------------

.PHONY: test
test: clean-testbench-migrations
	@./vendor/bin/phpunit --testdox --display-notices

# ---------------------------------------------------
# Code Quality Tools (Console Output Versions)
# ---------------------------------------------------

.PHONY: lint-php
lint-php:
	@echo "🛠️  Running Pint code formatter..."
	@$(PINT) --test
	@echo "✅ Pint formatting check completed"

.PHONY: lint-php-fix
lint-php-fix:
	@echo "🛠️  Running Pint code formatter..."
	@$(PINT)
	@echo "✅ Pint formatting applied"

.PHONY: lint-phpstan
lint-phpstan:
	@echo "🔍 Running PHPStan static analysis..."
	@$(PHPSTAN) analyse src tests --level=max
	@echo "✅ PHPStan analysis completed"

.PHONY: lint-rector
lint-rector:
	@echo "🔄 Running Rector refactoring..."
	@$(RECTOR) process
	@echo "✅ Rector refactoring completed"

.PHONY: lint-psalm
lint-psalm:
	@echo "📖 Running Psalm static analysis..."
	@$(PSALM) --show-info=true
	@echo "✅ Psalm analysis completed"

# ---------------------------------------------------
# Code Quality Tools (Markdown Report Versions)
# ---------------------------------------------------

.PHONY: lint-php-md
lint-php-md:
	@echo "📊 Running Pint and saving report to pint.md..."
	@echo "# Pint Code Formatter Report" > pint.md
	@echo "*Generated: $$(date)*" >> pint.md
	@echo "" >> pint.md
	@$(PINT) --test --verbose 2>&1 >> pint.md || true
	@echo "✅ Pint report saved to pint.md"

.PHONY: lint-php-fix-md
lint-php-fix-md:
	@echo "📊 Running Pint formatting test and saving report to pint-test.md..."
	@echo "# Pint Formatting Test Report" > pint-test.md
	@echo "*Generated: $$(date)*" >> pint-test.md
	@echo "" >> pint-test.md
	@$(PINT) --test 2>&1 >> pint-test.md || true
	@echo "✅ Pint formatting test report saved to pint-test.md"

.PHONY: lint-phpstan-md
lint-phpstan-md:
	@echo "📊 Running PHPStan and saving report to phpstan.md..."
	@echo "# PHPStan Static Analysis Report" > phpstan.md
	@echo "*Generated: $$(date)*" >> phpstan.md
	@echo "" >> phpstan.md
	@$(PHPSTAN) analyse src tests --level=max --no-progress 2>&1 >> phpstan.md || true
	@echo "✅ PHPStan report saved to phpstan.md"

.PHONY: lint-rector-md
lint-rector-md:
	@echo "📊 Running Rector and saving report to rector.md..."
	@echo "# Rector Refactoring Report" > rector.md
	@echo "*Generated: $$(date)*" >> rector.md
	@echo "" >> rector.md
	@$(RECTOR) process --dry-run 2>&1 >> rector.md || true
	@echo "✅ Rector report saved to rector.md"

.PHONY: lint-psalm-md
lint-psalm-md:
	@echo "📊 Running Psalm and saving report to psalm.md..."
	@echo "# Psalm Static Analysis Report" > psalm.md
	@echo "*Generated: $$(date)*" >> psalm.md
	@echo "" >> psalm.md
	@$(PSALM) --show-info=true --no-progress 2>&1 >> psalm.md || true
	@echo "✅ Psalm report saved to psalm.md"

.PHONY: clean-testbench-migrations
clean-testbench-migrations:
	@echo "🧹 Cleaning Orchestra Testbench migrations..."
	@rm -f vendor/orchestra/testbench-core/laravel/database/migrations/*_create_roster_*_table.php || true
	@echo "✅ Testbench migrations cleaned"

# ---------------------------------------------------
# Batch Quality Checks (Non-blocking)
# ---------------------------------------------------

.PHONY: lint-all-md
lint-all-md:
	@echo "📦 Running all code quality checks and saving reports..."
	@make lint-php-md
	@make lint-phpstan-md
	@make lint-psalm-md
	@echo "✅ All code quality reports generated"
	@echo "📋 Reports:"
	@echo "  - pint.md (Pint formatting)"
	@echo "  - phpstan.md (PHPStan analysis)"
	@echo "  - psalm.md (Psalm analysis)"

.PHONY: lint-all-fix-md
lint-all-fix-md:
	@echo "📦 Running all code fixers and saving reports..."
	@make lint-php-fix-md
	@make lint-rector-md
	@echo "✅ All code fixer reports generated"
	@echo "📋 Reports:"
	@echo "  - pint-test.md (Pint formatting test)"
	@echo "  - rector.md (Rector refactoring)"

# ---------------------------------------------------
# Release Management Workflow
# ---------------------------------------------------

.PHONY: pre-release
pre-release:
	@echo "🚀 Running pre-release checks..."
	@echo "📊 Generating quality reports..."
	@make test
	@make lint-all-md
	@echo "✅ Pre-release checks completed"
	@echo "📋 Review reports before release:"
	@echo "  - pint.md (formatting issues)"
	@echo "  - phpstan.md (static analysis errors)"
	@echo "  - psalm.md (type checking issues)"

.PHONY: release
release: pre-release
	@echo "🚀 Creating release..."
	@make git-tag
	@echo "✅ Release created successfully"

.PHONY: post-release
post-release:
	@echo "🧹 Performing post-release cleanup..."
	@make update-all
	@echo "✅ Post-release cleanup completed"

# ---------------------------------------------------
# Help & Documentation
# ---------------------------------------------------

.PHONY: help
help:
	@echo "📚 Available commands:"
	@echo ""
	@echo "🚀 Version Control:"
	@echo "  git-commit-push       Commit and push all changes"
	@echo "  git-tag               Create and push a new version tag"
	@echo "  generate-ai-diff      Generate clean diff for AI review"
	@echo "  git-tag-republish     Force push the last tag"
	@echo ""
	@echo "📁 File Management:"
	@echo "  update-checklist      Update file checklist"
	@echo "  list-modified-files   List modified files"
	@echo "  update-all            Update checklist and modified files"
	@echo "  concat-all            Concatenate all PHP files"
	@echo ""
	@echo "🧪 Testing:"
	@echo "  test                  Run PHPUnit tests"
	@echo ""
	@echo "🔍 Code Quality (Console - fails on error):"
	@echo "  lint-php              Run Pint code formatter"
	@echo "  lint-php-fix          Apply formatting with Pint"
	@echo "  lint-phpstan          Run PHPStan static analysis"
	@echo "  lint-rector           Apply refactoring with Rector"
	@echo "  lint-psalm            Run Psalm analysis"
	@echo ""
	@echo "📊 Code Quality (Markdown - non-blocking):"
	@echo "  lint-php-md           Run Pint and save report"
	@echo "  lint-php-fix-md       Test formatting and save report"
	@echo "  lint-phpstan-md       Run PHPStan and save results"
	@echo "  lint-rector-md        Run Rector and save report"
	@echo "  lint-psalm-md         Run Psalm and save results"
	@echo "  lint-all-md           Run all linters (non-blocking)"
	@echo "  lint-all-fix-md       Run all fixers (non-blocking)"
	@echo ""
	@echo "🔄 Release Management:"
	@echo "  pre-release           Run all pre-release checks"
	@echo "  release               Create new release (includes pre-release)"
	@echo "  post-release          Clean up after release"
	@echo ""
	@echo "❓ Help:"
	@echo "  help                  Display this help message"

# ---------------------------------------------------
# Default Target
# ---------------------------------------------------
.DEFAULT_GOAL := help