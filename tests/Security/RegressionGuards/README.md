# Security regression guards

Tests in this directory assert **invariants** on sensitive code paths (for example, legacy anonymous submission / pagination flows on `SubmissionsController`). They are **not** documenting a broken product surface — they prevent regressions on behaviour that was tightened for security.

Older docs may refer to a `KnownIssues/` folder name; that wording was misleading for support readers and was renamed to **`RegressionGuards/`** in Phase 8.
