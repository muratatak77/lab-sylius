@managing_customers
Feature: Browsing customer's newsletter subscription history
    In order to know since when a customer has agreed to receive the newsletter
    As an Administrator
    I want to browse the history of changes to the customer's newsletter subscription

    Background:
        Given the store has customer "f.baggins@shire.me" with name "Frodo Baggins" since "2011-01-10 21:00"
        And I am logged in as an administrator

    @api
    Scenario: Browsing the history of a customer who subscribed to the newsletter and then unsubscribed from it
        Given the customer subscribed to the newsletter
        And the customer unsubscribed from the newsletter
        When I browse the newsletter subscription history of the customer "f.baggins@shire.me"
        Then I should see 2 entries in the newsletter subscription history
        And the latest entry should show that the customer unsubscribed from the newsletter

    @api
    Scenario: Seeing a new entry in the history after making a customer subscribed to the newsletter
        When I want to edit this customer
        And I make them subscribed to the newsletter
        And I save my changes
        And I browse the newsletter subscription history of the customer "f.baggins@shire.me"
        Then I should see 1 entry in the newsletter subscription history
        And the latest entry should show that the customer subscribed to the newsletter

    @api
    Scenario: Seeing since when a customer has been subscribed to the newsletter
        Given the customer subscribed to the newsletter
        When I view details of the customer "f.baggins@shire.me"
        Then I should see that this customer has been subscribed to the newsletter since a moment ago

    @api
    Scenario: Seeing an empty history of a customer who has never subscribed to the newsletter
        When I browse the newsletter subscription history of the customer "f.baggins@shire.me"
        Then I should see 0 entries in the newsletter subscription history
