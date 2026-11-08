# Newsletter subscription history in a dedicated log table

* Status: accepted
* Date: 2026-11-05

## Context and Problem Statement

A customer's newsletter consent is stored as a single `subscribed_to_newsletter` boolean. Merchants need to say since
when a customer has been subscribed and how their consent changed over time, typically to answer GDPR requests or to
reconcile with an external newsletter provider. How should Sylius keep that information, and what should it do with
customers who subscribed before it was recorded?

## Decision Drivers

* the current state ("subscribed since") must be cheap to read and to filter on
* the history must be easy to query per customer and expose through the API
* changes must be recorded whichever way they happen: admin panel, shop, API or custom code
* existing subscribers must not end up with consent dates that are earlier than what can be proven

## Considered Options

### Gedmo Loggable on the customer

Mark `subscribedToNewsletter` as versioned, as is done for addresses with `AddressLogEntry`.

* Good, because the infrastructure already exists
* Bad, because the data is stored serialized, so "who subscribed between X and Y" cannot be queried efficiently
* Bad, because versioning more customer fields later would mix unrelated changes into the consent history
* Bad, because it gives no denormalized "subscribed since" value

### Recording changes in command handlers and controllers

Create the log entry wherever the flag is changed.

* Good, because the intent of the change is known at that point
* Bad, because there are several such places (registration, account update, admin form, admin API, fixtures) and
  custom code would silently bypass them

### Dedicated log table filled by an entity observer, plus a denormalized column

Add `sylius_customer.subscribed_to_newsletter_at`, maintained by `Customer::setSubscribedToNewsletter()`, and a
`sylius_newsletter_subscription_log_entry` table filled by an entity observer plugged into the existing
`OnFlushEntityObserverListener`, the same mechanism the price history relies on.

* Good, because every change going through the unit of work is recorded, regardless of its origin
* Good, because the history is a plain table that is easy to query and to expose via API Platform
* Good, because reading the current state needs no join
* Bad, because changes done with DQL or SQL bulk updates are not recorded

## Decision Outcome

Chosen option: "Dedicated log table filled by an entity observer, plus a denormalized column", because it is the only
option that records all changes and keeps both the current state and the history queryable.

A newly created customer always has `subscribedToNewsletter` in its change set, so the observer only logs an insert
when the customer is subscribed. Otherwise every guest checkout would produce an "unsubscribed" entry.

Existing subscribers are backfilled in the migration that creates the schema. Their real opt-in date is unknown, so
`COALESCE(updated_at, created_at)` is used for both the column and the single "subscribed" log entry. At that moment
the subscription was certainly active, which is not true of `created_at` for customers who subscribed later.
