# Contributing

Thank you for helping keep this benchmark useful and auditable.

## Development workflow

1. Create a focused branch from the current default branch.
2. Run `make setup` once.
3. Make the smallest change that addresses the issue.
4. Run `make ci` and the relevant renderer smoke test.
5. Explain any methodology or result-schema change in the pull request.

Do not commit `.env`, credentials, local cost snapshots, or working runs. Published runs must come from a clean commit on a declared benchmark host. Full/fidelity runs must include completed fidelity review; capacity-only runs do not contain fidelity artifacts and should be paired with a reviewed fidelity run from the same code revision.

## Methodology changes

Changes to templates, data, asset handling, renderer settings, timing boundaries, scheduling, warm-up, concurrency, percentiles, resource sampling, or summary selection can change results. Such pull requests must:

- state why the previous behavior was insufficient;
- add or update automated tests;
- increment the result schema when compatibility changes;
- avoid comparing runs created before and after the change as equivalent;
- update the English README and generated report labels.

Never tune only one core renderer unless the variation is explicitly named and reported separately. Renderer-specific remediation belongs in the asset capability pass, not in common performance fixtures.

One run represents exactly one benchmark host, CPU allocation class, region, and managed-plan declaration. Capacity sweeps with different physical servers or provider plans belong in separate run IDs. Do not relabel the in-process worker pool as a separate load generator.

## Quality checks

```bash
make ci
make smoke
```

PHP code must pass Pint, PHPStan, and PHPUnit. JavaScript assets must build from the committed npm lock. New PDF fixtures need deterministic data, a documented expected page count, feature crop definitions, and manual-review entries.

## Reporting issues

Include the host label, region label, Git SHA, run ID, relevant raw block, and a minimal reproduction. Remove tokens, account identifiers, hostnames, IP addresses, and private document content before posting.
