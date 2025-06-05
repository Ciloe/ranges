# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
