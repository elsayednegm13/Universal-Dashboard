.PHONY: verify doctor release-check up down logs test-static

verify:
	php tools/verify.php

doctor:
	php tools/doctor.php

release-check:
	php tools/release-check.php

test-static: verify
	find . -type f -name '*.php' -not -path './storage/*' -print0 | xargs -0 -n1 php -l

up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f web db
