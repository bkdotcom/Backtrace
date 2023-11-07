# Backtrace
PHP Backtrace utils

## is this really necessary?
* There are two functions to get a backtrace in PHP.  `debug_backtrace` and `xdebug_get_function_stack`...  To get the trace for a fatal errpr. `xdebug_get_function_stack` must be used.
This utility uses xdebug when necessary and normalizes the results.
* Ability to define classes/namespaces to skip over...  Useful for when your framework wants to display a trace to the user... without all the internals that got to the point of generating the trace
* Utility for getting the surrounding file lines for each frame.
* `getCallerInfo()` utility method to get the calling file/line/function

## Tests / Quality

![No Dependencies](https://img.shields.io/badge/dependencies-none-333333.svg)
![Supported PHP versions](https://img.shields.io/static/v1?label=PHP&message=5.4%20-%208.3&color=blue)
![Build Status](https://img.shields.io/github/actions/workflow/status/bkdotcom/Backtrace/phpunit.yml.svg?logo=github)
[![Maintainability](https://img.shields.io/codeclimate/maintainability/bkdotcom/Backtrace.svg?logo=codeclimate)](https://codeclimate.com/github/bkdotcom/Backtrace)
[![Coverage](https://img.shields.io/codeclimate/coverage-letter/bkdotcom/Backtrace.svg?logo=codeclimate)](https://codeclimate.com/github/bkdotcom/Backtrace)
