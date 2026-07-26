## ADDED Requirements

### Requirement: Package Metadata
The `composer.json` SHALL identify the project as `flowarr/flowarr` with an accurate description and relevant keywords.

#### Scenario: Composer package name
- **WHEN** inspecting the composer.json
- **THEN** the `name` field SHALL be `flowarr/flowarr`
- **THEN** the `type` field SHALL remain `project`
- **THEN** the `description` SHALL describe the project as a media library automation tool

### Requirement: Software License
A valid MIT `LICENSE` file SHALL be present in the project root matching the declared license in `composer.json`.

#### Scenario: License file exists
- **WHEN** checking the project root
- **THEN** a `LICENSE` file containing the MIT license text SHALL exist

### Requirement: Application Name
The application name in `config/app.php` SHALL use "Flowarr" instead of any starter-kit default.

#### Scenario: App name configured
- **WHEN** reading `config('app.name')`
- **THEN** it SHALL return "Flowarr"
