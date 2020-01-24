# Changelog

###### v1.0.1
- new method on AuditTrailBehavior to fetch the AuditTrailEntries

###### v1.0.0
- dependency update (__potential breaking change!__)

###### v0.8.4
- Bugfixes concerning compatibility with PHP 7.2

###### v0.8.3
This release fixes minor bugs and updates the dependencies

Issues fixed:
- #7: Class AuditTrailEntry uses table name without prefix

Other changes:
- dependency update

###### v0.8.2
Extended the functionality to detect changes (now respecting types). Furthermore added the possibility to specify whether
or not strings should get compared case-sensitive and if empty strings should be considered as null. Both of these
features can be configured via attributes of `AuditTrailBehavior`.

Issued fixed:
- #2: No record should be created if only changed attribute is in ignored attribute array (plm57)

Other changes:
- dependency update

###### v0.8.1
Dependencies updated, otherwise no changes.

###### v0.8.0
Main classes in a stable condition and further features will be added in a backwards-compatible way.
All breaking changes will lead to a new minor version.
