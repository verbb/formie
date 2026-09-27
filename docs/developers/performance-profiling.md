# Performance Profiling

Formie includes a repeatable profiler for builder, rendering, browser bootstrap and submission journeys. Run it only against the dedicated test environment:

```bash
ddev test --task=profile run all --profile=medium --iterations=10 --format=ndjson
```

Profiles cover builder load/edit-save, full render, manual assets, Summary fragments, client-rendered bootstrap, complete submit, resume and revise. Use `small`, `medium` and `large` fixtures to distinguish fixed overhead from layout-size growth.

## Reading Results

Each scenario records query count, wall time and peak memory. Compare warm repeated runs from the same machine and database engine. A single cold run is useful for diagnosis but is not evidence of an improvement.

Keep the raw before/after output with the change. Any claimed optimisation should include:

- the exact fixture, scenario and iteration count;
- before and after query, wall-time and memory values;
- a functional regression test for the removed work;
- repeated-render and invalidation coverage where a cache is involved.

Do not weaken functional assertions to satisfy a budget. Investigate regressions relative to the established programme baseline.

## Cache Policy

Prefer request- or instance-scoped memoisation with an explicit invalidation point. Broad persistent caching of layouts or rendered HTML is not a default optimisation: it risks stale builder, project-config and portability behaviour.

Form getters must not mutate layout structure during export or render. Formie deliberately keeps the page/row/field hierarchy and the Workstream 04 serializer/remapper as the authoritative portability path.
