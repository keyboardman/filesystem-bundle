.PHONY: help test test-coverage test-filter clean

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

test: ## Lance les tests PHPUnit
	./vendor/bin/phpunit

test-coverage: ## Lance les tests avec rapport de couverture (sortie dans ./coverage)
	./vendor/bin/phpunit --coverage-html ./coverage

test-filter: ## Lance les tests avec un filtre (usage: make test-filter FILTER=TestName)
	./vendor/bin/phpunit --filter $(FILTER)

clean: ## Supprime le rapport de couverture
	rm -rf ./coverage
