@paygw @paygw_mpay24 @javascript
Feature: MPay24 basic configuration and useage by user
  In order buy shopping_cart items as a user
  I configure MPay24 in background to use company corporative account.

  Background:
    Given the following "users" exist:
      | username | firstname  | lastname    | email                       |
      | user1    | Username1  | Test        | toolgenerator1@example.com  |
      | user2    | Username2  | Test        | toolgenerator2@example.com  |
      | teacher  | Teacher    | Test        | toolgenerator3@example.com  |
      | manager  | Manager    | Test        | toolgenerator4@example.com  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | user1    | C1     | student        |
      | user2    | C1     | student        |
      | teacher  | C1     | editingteacher |
    And the following "core_payment > payment accounts" exist:
      | name    |
      | MPay24  |
    And the following "paygw_mpay24 > configuration" exist:
      | account  | gateway | enabled |
      | MPay24   | mpay24  | 1       |
    And the following "local_shopping_cart > plugin setup" exist:
      | account |
      | MPay24  |

  @javascript
  Scenario: MPay24: user select two items and pay via card using MPay24
    Given I log in as "user1"
    And Shopping cart has been cleaned for user "user1"
    And Testitem "1" has been put in shopping cart of user "user1"
    And Testitem "2" has been put in shopping cart of user "user1"
    And I visit "/local/shopping_cart/checkout.php"
    And I wait until the page is ready
    And I should see "Your shopping cart"
    And I should see "Test item 1" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-1" "css_element"
    And I should see "10.00 EUR" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-1 .item-price" "css_element"
    And I should see "Test item 2" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-2" "css_element"
    And I should see "20.30 EUR" in the ".checkoutgrid.checkout #item-local_shopping_cart-main-2 .item-price" "css_element"
    ## Price
    And I should see "30.30 EUR" in the ".sc_price_label" "css_element"
    Then I press "Checkout"
    And I wait until the page is ready
    And I should see "mpay24" in the ".core_payment_gateways_modal" "css_element"
    And I should see "Cost: EUR" in the ".core_payment_fee_breakdown" "css_element"
    And I should see "30.30" in the ".core_payment_fee_breakdown" "css_element"
    And I press "Proceed"
    ## npay service
    And I wait to be redirected
    And I wait until the page is ready
    And I wait "1" seconds
    And I click on "VISA" "text"
    And I click on "Weiter" "button"
    And I wait until the page is ready
    And I set the field "cardnumber" to "4444333322221111"
    And I set the field "expiry_mm" to "05"
    And I set the field "expiry_yy" to "2035"
    And I set the field "cvc" to "123"
    And I wait "1" seconds
    And I click on "Bezahlen" "button"
    And I wait until the page is ready
    And I should see "Transaktion erfolgreich!"
    And I click on "Weiter" "button"
    ## Workaround for non-https dev env (uncomment line below for local testing)
    ## And I click on "Send anyway" "text"
    And I wait to be redirected
    And I wait until the page is ready
    And I should see "Test item 1" in the ".payment-success ul.list-group" "css_element"
    And I should see "Test item 2" in the ".payment-success ul.list-group" "css_element"
