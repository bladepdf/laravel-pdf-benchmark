# Security Policy

## Reporting a vulnerability

Please use GitHub private vulnerability reporting for this repository. Do not open a public issue containing an exploitable vulnerability, provider credential, account identifier, hostname, IP address, or private benchmark document.

## Benchmark data

The suite is designed to redact secret-looking values before results are written, but the operator remains responsible for reviewing a publishable run. Keep `.env` and `ops/costs.local.json` local. Use synthetic fixture data only.

The application receives read-only access to the Docker socket for service metrics and controlled restarts. Treat that access as privileged and run the suite only on a dedicated or otherwise trusted benchmark host.
