# solsearch
A service to store and retrieve geopositoned ads

Solsearch allows many 'advertisers' to 'post' their adverts, and allows any client to list and search across all ads.

It will have the features:

* provide a minimal implementation of an 'ad' that provides baseline functionality but flexibility to be used by advertisers for any product or service
* allow advertisers full control over their adverts
* provide public search and list features - where all advert data is treated as 'public'

It will achieve this through:

* A single API that allows writing, listing and searching

# API Specification

The API will be delivered through a https REST API.

The calls available on the API are:

* add
* update
* delete
* bulkAdd
* bulkUpdate
* bulkDelete
* search

How to register a new group:

POST http://solsearch/client
{
  "url" : "newgroup.org",
  "name": "My New Group"
}
