# 

Version: $Id$ ($Date$)

# History

## 0.2.0 (2025 Aug 07)

- `Client::sendRequest` works correctly now

- `Client` file serve improvements

- 'Request' & `Response` generally improvements all over

- update: to latest `psr` interface versions

- fix : `AbstractRequest` now parses uri correctly (get complete url not
  just path) when none supplied

- moved to new document structure

- bring back inline with **psr**

## 0.1.4 (2023 May 03)

- request: minor fixes in handling a terminal environment

- doc: updates

- update: removal of redundant classes

- update: `Stream` remove dynamic property `$uri`

- update: `Stream` add return types

- fix: `AbstractRequest` check for null values

- fix: `Request` create properties explicitly (no longer uses dynamic
  properties)

## 0.1.3 (2022 Aug 03)

- request stringable as uri

- added request::getHttpMethod: HttpMethod

## 0.1.2 (2022 Aug 02)

- Fix: error on invalid file in response.

- Update dependency inanepain/stdlib:0.1.4 to fix mime parsing error

## 0.1.0 (2022 May 31)

- Improved some variable names for easier readability

- Request now imports the current headers if none specified
