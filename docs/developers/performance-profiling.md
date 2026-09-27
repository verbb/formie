# Performance Profiling

When contributing performance changes to Formie, use the repository’s DDEV test environment to compare builder, rendering and submission timings. This command requires that test environment; it is not a command for an installed production site:

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

Check that the same forms still behave correctly after the change, including repeated renders and edits to cached settings.

## Cache Policy

Prefer request- or instance-scoped memoisation with an explicit invalidation point. Broad persistent caching of layouts or rendered HTML is not a default optimisation: it risks stale builder, project-config and portability behaviour.

Reading or rendering a form must leave its layout unchanged. Use the existing import/export services when copying forms so field references stay consistent.
