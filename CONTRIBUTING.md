CONTRIBUTING
============

Report an issue
================

Try to describe your issue with precision and try to give examples to reproduce it. Describing what you are trying
to achieve will also help.

The more precise you are, the faster it might be solved.



Pull Request
============

You are welcome to contribute. If you want to make a large change or implement new features, please discuss it before.
That will help make sure that your contribution goes in the good direction.

Tests
-----

All contributions must be tested following as much as possible the current test structure in ``test/suites``
and the test classes must be annotated with ``@covers``.

To run test suit: ``composer test``. Make sure that it passes before you send your pull request.

Coding Standards
-----------------

The code follow the PSR-2 coding standards, you can:

- Check Coding standards: ``composer cscheck``
- auto fix standards: ``.composer csfix``
