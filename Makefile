# Requires Docker. Run `make` to list the targets.

DOCKER_IMAGE := ghcr.io/typo3-documentation/render-guides:latest
DOCKER_USER := $(shell id -u):$(shell id -g)
DOCS_DIR := Documentation
DOCS_OUTPUT := Documentation-GENERATED-temp

.DEFAULT_GOAL := help

.PHONY: help
help: ## Display available commands
	@echo "Usage: make [target]\n"
	@echo "Targets:"
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-20s\033[0m %s\n", $$1, $$2}'

.PHONY: docs
docs: ## Build documentation locally
	mkdir -p $(DOCS_OUTPUT)
	docker run --user $(DOCKER_USER) --rm --pull always -v "$(CURDIR)":/project -t $(DOCKER_IMAGE) --config=$(DOCS_DIR)

.PHONY: test-docs
test-docs: ## Render the documentation and fail on warnings
	mkdir -p $(DOCS_OUTPUT)
	docker run --user $(DOCKER_USER) --rm --pull always -v "$(CURDIR)":/project -t $(DOCKER_IMAGE) --config=$(DOCS_DIR) --no-progress --minimal-test

.PHONY: docs-hot
docs-hot: ## Serve the documentation on port 1337, re-rendering on change
	mkdir -p $(DOCS_OUTPUT)
	docker run --user $(DOCKER_USER) --rm -it --pull always \
		-v "$(CURDIR)/$(DOCS_DIR)":/project/$(DOCS_DIR) \
		-v "$(CURDIR)/$(DOCS_OUTPUT)":/project/$(DOCS_OUTPUT) \
		-p 1337:1337 $(DOCKER_IMAGE) --config=$(DOCS_DIR) --watch

.PHONY: docs-open
docs-open: ## Open rendered documentation in browser
	@if [ -f "$(DOCS_OUTPUT)/Index.html" ]; then \
		xdg-open "$(DOCS_OUTPUT)/Index.html" 2>/dev/null || open "$(DOCS_OUTPUT)/Index.html" 2>/dev/null || echo "Open $(DOCS_OUTPUT)/Index.html in your browser"; \
	else \
		echo "Documentation not found. Run 'make docs' first."; \
	fi

.PHONY: clean
clean: ## Remove generated documentation
	rm -rf $(DOCS_OUTPUT)
	@echo "Cleaned $(DOCS_OUTPUT)"