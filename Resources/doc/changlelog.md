# v3.1.3
## Tests
- Line coverage raised from 91.26% to 100% (538/538), methods to 100% (56/56). Suite grew from
  111 to 124 tests.
- `SocketWriter::write()` was the large gap at 44.90%, untestable by mocking because it opens
  its own socket. Now covered by binding a server on an ephemeral loopback port: the sync TCP
  write, the async UDP fragmentation path, and the `charactersToRead` read-back.
- Also covered: `ErrorHandlers::onShutdown()`, every `guessProtocol()`/`guessHost()`/
  `guessPort()` branch behind the request URL, the empty-backtrace branch, the user-agent filter
  skip, and `getWriter()` falling back to `default_writer`.

No source changes; nothing in this release affects runtime behaviour.

# v3.1.2
## Fixes
- `phpunit.xml.dist` did not validate. It used the PHPUnit 8 `<log type="coverage-clover">`
  element, which 9.x moved under `<coverage>`, so every run printed "The configuration file did
  not pass validation!" and the element was ignored. Removed, and the schema location bumped
  from 9.3 to 9.6 to match the pinned PHPUnit.
- The `test` composer script passed `--coverage-xml`, which takes a directory, so
  `build/logs/clover.xml` was created as a directory of per-file PHPUnit XML. The `coveralls`
  script expects a clover file at that path and so had nothing valid to read. Now
  `--coverage-clover`.

No runtime changes; this release only affects the test setup.

# v3.1.1
## Bug fixes
- An empty pattern in `params_filters`, or an empty key in `backtrace_filters`, was passed
  straight to `preg_match()`/`preg_replace()`, which raise "Empty regular expression". In
  `filterTrace()` the resulting `null` also stopped the remaining filters applying to that
  trace. Empty patterns are now skipped.
- A non-string `$_SERVER` value (`HTTP_HOST`, `SERVER_NAME`, `REQUEST_URI`,
  `HTTP_X_FORWARDED_PROTO`) raised "Array to string conversion" while building the notice,
  inside the error reporting path, where it masks the exception being reported. Reads now go
  through a helper that coerces scalars and falls back to `''`.

## Changes
- `Errbit::$writer` is now `?WriterInterface` defaulting to `null`, matching its lazy
  initialisation in `getWriter()`. Only relevant if you subclass `Errbit`.
- Resolved all 14 psalm 6 errors in `src/`; psalm now reports no errors.
- Fixed the README build badge, which pointed at a workflow file that does not exist.

# v3.1.0
## Security
- `phpunit/phpunit` was pinned to the exact version `9.4.4`, affected by CVE-2026-24765 (high):
  unsafe deserialization of pre-existing `.coverage` files in the PHPT runner. Now `^9.6.33`.
  Dev-only dependency, so it affects contributors and CI rather than consuming applications.

## Changes
- `mockery/mockery`: exact `1.5.1` -> `^1.6`, clearing implicit-nullable deprecations on PHP 8.4.
- Added `config.platform.php`, so dependencies resolve against the lowest supported PHP.
- Minimum PHP is now 8.2. The manifest previously still allowed `^8.1` while this changelog and
  the README documented 8.2+ for the whole 3.x line.
- CI: dropped the PHP 8.0 job, fixed a vendor cache key that hashed the uncommitted
  `composer.lock` and could restore one PHP version's `vendor` into another.

# v2.0.0
## New features
- Major rewrite to php8.0
- New composer dependencies
- new Writer based on Guzzle HttpClient. works in sync and async.

## Deprecations:
- dropped php5.3 support
- 


# v1.1.1
Last version with support of php5.3+
