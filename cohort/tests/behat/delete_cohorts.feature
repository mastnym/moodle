@core @core_cohort
Feature: Delete multiple cohorts
  In order to delete multiple cohorts
  As an admin
  I need to select specific cohorts and delete them

  Background:
    Given I log in as "admin"
    And I navigate to "Cohorts" node in "Site administration > Users > Accounts"
    And I follow "Add new cohort"
    And I set the following fields to these values:
      | Name | Test cohort name 1 |
      | Context | System |
      | Cohort ID | 333 |
      | Description | Test cohort description 1 |
    And I press "Save changes"
    And I follow "Add new cohort"
    And I set the following fields to these values:
      | Name | Test cohort name 2 |
      | Context | System |
      | Cohort ID | 334 |
      | Description | Test cohort description 2 |
    And I press "Save changes"
    And I follow "Add new cohort"
    And I set the following fields to these values:
      | Name | Test cohort name 3 |
      | Context | System |
      | Cohort ID | 335 |
      | Description | Test cohort description 3 |
    And I press "Save changes"

  @javascript
  Scenario: Delete multiple cohorts
    Given I follow "Cohorts"
    Then I should see "Test cohort name 1"
    Then I should see "Test cohort name 2"
    Then I should see "Test cohort name 3"
    And I should see "333"
    And I should see "334"
    And I should see "335"
    And I should see "Test cohort description 1"
    And I should see "Test cohort description 2"
    And I should see "Test cohort description 3"
    When I follow "Select all/none"
    And I press "Delete selected cohorts"
    And I press "Continue"
    And I wait to be redirected
    Then I should not see "Test cohort name 1"
    And I should not see "Test cohort name 2"
    And I should not see "Test cohort name 3"
    And I should not see "333"
    And I should not see "334"
    And I should not see "335"
    And I should not see "Test cohort description 1"
    And I should not see "Test cohort description 2"
    And I should not see "Test cohort description 3"
