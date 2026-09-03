# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-09-03

### Added
- `TimeRange` class for working with time-of-day ranges (comparisons ignore the date part)
- Documentation for TimeRange (`doc/TimeRange.md`) and a TimeRange section in the README

### Changed
- **Breaking:** PHP 8.3 or higher is now required (previously 8.2); PHP 8.2 has been removed from the CI matrix
- **Breaking:** exclusive bounds are now shifted by one step instead of 1 in `IntRange` and `BigIntRange`, aligning them with `DateRange` and `TimeRange` (e.g. `(0,20)` with step 5 has effective bounds 5 and 15)
- **Breaking:** `union()` and `intersection()` now produce exclusive infinite bounds, so their string representation can always be parsed back with `fromString()`
- **Breaking:** `generateSeries()` no longer throws when the step is larger than the range span; it returns the partial series (e.g. `[1,5]` with step 10 yields `[1]`), consistent with `length()`
- `IntRange::union()` and `IntRange::intersection()` now propagate the common step instead of resetting it to 1
- `DateRange` steps are measured against a fixed reference date, making `length()`, `union()`, `intersection()` and `equals()` deterministic for monthly/yearly steps regardless of the current date

### Fixed
- `TimeRange::length()` and `BigIntRange::length()` returned a wrong count when the span was not a multiple of the step
- `TimeRange::generateSeries()` looped forever (until memory exhaustion) when the step crossed midnight without exceeding the upper bound
- `DateRange` and `TimeRange` accepted zero or negative/inverted steps, leading to division by zero in `length()` and infinite loops in `generateSeries()`; they now throw `InvalidDateIntervalException` / `InvalidTimeIntervalException`
- `DateRange::contains()` compared full timestamps: a value with a time component on the upper bound day was wrongly rejected; comparisons now use the date only
- `DateRange::fromString()` and `TimeRange::fromString()` leaked a raw `DateMalformedStringException` for impossible values matching the pattern (e.g. `2025-13-45`, `25:99:99`); they now throw `InvalidArgumentException`
- `IntRange::generateSeries()` could leak an uncaught `ValueError` from the native `range()` function

### Removed
- **Breaking:** `InvalidStepToGenerateSeriesException` (no longer thrown anywhere)

## [0.0.2] - 2025-06-11

### Added
- `DateRange` class for working with date ranges
- Support for date range operations:
  - Creating date ranges with inclusive/exclusive bounds
  - Checking if a date is contained in a range
  - Checking if date ranges overlap
  - Calculating date range length
  - Creating unions and intersections of date ranges
  - Generating series of dates within a range
  - Comparing date ranges for equality
  - Splitting date ranges at specific points
  - Shifting date ranges by time intervals
- Custom step intervals (days, weeks, months, etc.) for date ranges
- Documentation for DateRange

## [0.0.1] - 2025-06-04

### Added
- Initial release of the Ranges library
- `RangeInterface` defining the common API for all range types
- `IntRange` class for working with integer ranges
- `BigIntRange` class for working with arbitrary precision integer ranges
- Support for range operations:
  - Creating ranges with inclusive/exclusive bounds
  - Checking if a value is contained in a range
  - Checking if ranges overlap
  - Calculating range length
  - Creating unions and intersections of ranges
  - Generating series of values within a range
  - Comparing ranges for equality
  - Splitting ranges at specific points
  - Shifting and scaling ranges
- Comprehensive exception handling
- Full test coverage
- Documentation for all range types
