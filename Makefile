SHELL := /bin/sh
RUN ?=
PROFILE ?= full
RENDERERS ?=
TEMPLATES ?=
CONCURRENCY ?=
ITERATIONS ?=
RESUME ?=
COSTS ?=
DRAFT ?=

.PHONY: setup preflight benchmark review report smoke test ci ops-install

setup:
	@test -f .env || cp .env.benchmark.example .env
	docker compose build --pull
	docker compose run --rm --no-deps app composer install --no-interaction --prefer-dist
	docker compose run --rm --no-deps app npm ci
	docker compose run --rm --no-deps app php artisan key:generate
	docker compose run --rm --no-deps app npm run assets
	docker compose run --rm --no-deps app npm run build

preflight:
	docker compose up -d gotenberg chromium-persistent
	docker compose run --rm app php artisan benchmark:preflight $(if $(RENDERERS),--renderers=$(RENDERERS),)

benchmark:
	docker compose up -d gotenberg chromium-persistent
	docker compose run --rm app php artisan benchmark:run --profile=$(PROFILE) $(if $(RUN),--run-id=$(RUN),) $(if $(RENDERERS),--renderers=$(RENDERERS),) $(if $(TEMPLATES),--templates=$(TEMPLATES),) $(if $(CONCURRENCY),--concurrency=$(CONCURRENCY),) $(if $(ITERATIONS),--iterations=$(ITERATIONS),) $(if $(RESUME),--resume,)

review:
	@test -n "$(RUN)" || (echo "RUN is required" && exit 2)
	docker compose run --rm -p 127.0.0.1:8000:8000 app php artisan benchmark:review $(RUN) --host=0.0.0.0 --port=8000

report:
	@test -n "$(RUN)" || (echo "RUN is required" && exit 2)
	docker compose run --rm app php artisan benchmark:report $(RUN) $(if $(COSTS),--costs=$(COSTS),) $(if $(DRAFT),--allow-unreviewed,)

smoke:
	$(MAKE) benchmark PROFILE=smoke RENDERERS=dompdf,browsershot,gotenberg

test:
	docker compose run --rm --no-deps app composer test

ci:
	docker compose run --rm --no-deps app composer lint
	docker compose run --rm --no-deps app composer analyse
	docker compose run --rm --no-deps app composer test

ops-install:
	BENCHMARK_OPS_NETWORK=enabled npm run ops-install
