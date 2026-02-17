.PHONY: help build run test test-local test-coverage test-filter shell clean rebuild

# Variables
IMAGE_NAME = keyboardman/filesystem-bundle
CONTAINER_NAME = filesystem-bundle
PORT = 8000

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

build: ## Construit l'image Docker
	docker build -t $(IMAGE_NAME) .

rebuild: ## Reconstruit l'image Docker sans utiliser le cache
	docker build --no-cache -t $(IMAGE_NAME) .

run: ## Lance la démo (serveur sur le port 8000)
	docker run --rm -p $(PORT):8000 --name $(CONTAINER_NAME) $(IMAGE_NAME)

run-detached: ## Lance la démo en arrière-plan
	docker run -d -p $(PORT):8000 --name $(CONTAINER_NAME) $(IMAGE_NAME)

test: ## Lance les tests PHPUnit dans Docker
	docker run --rm $(IMAGE_NAME) ./vendor/bin/phpunit

test-local: ## Lance les tests PHPUnit localement (sans Docker)
	./vendor/bin/phpunit

test-coverage: ## Lance les tests avec rapport de couverture (sortie dans ./coverage)
	@docker rm -f $(CONTAINER_NAME)-coverage >/dev/null 2>&1 || true
	docker run --name $(CONTAINER_NAME)-coverage $(IMAGE_NAME) ./vendor/bin/phpunit --coverage-html /app/coverage
	docker cp $(CONTAINER_NAME)-coverage:/app/coverage ./coverage
	@docker rm -f $(CONTAINER_NAME)-coverage >/dev/null 2>&1 || true

test-filter: ## Lance les tests avec un filtre (usage: make test-filter FILTER=TestName)
	docker run --rm $(IMAGE_NAME) ./vendor/bin/phpunit --filter $(FILTER)

shell: ## Ouvre un shell interactif dans le conteneur
	docker run --rm -it --entrypoint /bin/bash $(IMAGE_NAME)

stop: ## Arrête le conteneur en cours d'exécution
	docker stop $(CONTAINER_NAME) || true
	docker rm $(CONTAINER_NAME) || true

clean: stop ## Nettoie les conteneurs et images
	docker rmi $(IMAGE_NAME) || true

logs: ## Affiche les logs du conteneur
	docker logs -f $(CONTAINER_NAME)
