CONTRIBUTING
============

Any contribution is welcome.

Issue
=====

Try to describe your issue with precision and try to give examples to reproduce it.

Pull Request
============

Tests
-----

All contributions must be tested following as much as possible the current test structure: 
one class = one test file in ``test/suites`` and the class must be annotated with ``@covers``.

To run test suit: ``composer test``. Make sure that it passes before you send your pull request.

Coding Standards
-----------------

The code follow the PSR-2 coding standards, you can:

- Check Coding standards: ``composer cscheck``
- auto fix standards: ``.composer csfix``
