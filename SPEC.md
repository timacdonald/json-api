## Content negotiation

### Server

- [ ] Servers **MUST** send all JSON:API data in response documents with the header `Content-Type: application/vnd.api+json` without any media type parameters.

- [ ] Servers **MUST** respond with a `415 Unsupported Media Type` status code if a request specifies the header `Content-Type: application/vnd.api+json` with any media type parameters.

- [ ] Servers **MUST** respond with a `406 Not Acceptable` status code if a request's `Accept` header contains the JSON:API media type and all instances of that media type are modified with media type parameters.

## Document structure

- [ ] Unless otherwise noted, objects defined by this specification **MUST NOT** contain any additional members. Client and server implementations **MUST** ignore members not recognized by this specification.

### Top level

- [ ] A JSON object **MUST** be at the root of every JSON:API request and response containing data. This object defines a document's "top level".

- [ ] A document **MUST** contain at least one of the following top-level members:
    - `data`: the document's "primary data"
    - `errors`: an array of [error objects](#errors)
    - `meta`: a [meta object][meta] that contains non-standard meta-information.

- [ ] A document MAY contain any of these top-level members:
    - `jsonapi`: an object describing the server's implementation
    - `links`: a [links object][links] related to the primary data.
    - `included`: an array of [resource objects] that are related to the primary data and/or each other ("included resources").

- [ ] If a document does not contain a top-level `data` key, the `included` member **MUST NOT** be present either.

#### Top level: links

- [ ] The top-level [links object][links] **MAY** contain the following members:
    - `self`: the [link][links] that generated the current response document.
    - `related`: a [related resource link] when the primary data represents a resource relationship.
    - [pagination] links for the primary data.

#### Top level: primary data

- [ ] a single [resource object][resource objects], a single [resource identifier object], or `null`, for requests that target single resources
- [ ] an array of [resource objects], an array of [resource identifier objects][resource identifier object], or an empty array (`[]`), for requests that target resource collections
- [ ] A logical collection of resources **MUST** be represented as an array, even if it only contains one item or is empty.

### Resource objects

- [ ] A resource object **MUST** contain at least the following top-level members:
    - `id`
    - `type`

> Exception: The `id` member is not required when the resource object originates at the client and represents a new resource to be created on the server.

- [ ] In addition, a resource object **MAY** contain any of these top-level members:
    - `attributes`: an [attributes object][attributes] representing some of the resource's data.
    - `relationships`: a [relationships object][relationships] describing relationships between the resource and other JSON:API resources.
    - `links`: a [links object][links] containing links related to the resource.
    - `meta`: a [meta object][meta] containing non-standard meta-information about a resource that can not be represented as an attribute or relationship.

#### Resource objects: identifiers

- [ ] Every [resource object][resource objects] **MUST** contain an `id` member and a `type` member.
- [ ] The values of the `id` and `type` members **MUST** be strings.
- [ ] Within a given API, each resource object's `type` and `id` pair **MUST** identify a single, unique resource. (The set of URIs controlled by a server, or multiple servers acting as one, constitute an API.)
- [ ] The `type` member is used to describe [resource objects] that share common attributes and relationships.
- [ ] The values of `type` members **MUST** adhere to the same constraints as [member names].

```
Note to self: adhere to the same constraints as member names. Recommendations for member names is camelCase
```

> Note: This spec is agnostic about inflection rules, so the value of `type` can be either plural or singular. However, the same value should be used consistently throughout an implementation.

#### Resource objects: fields

- [ ] Fields for a [resource object][resource objects] **MUST** share a common namespace with each other and with `type` and `id`. In other words, a resource can not have an attribute and relationship with the same name, nor can it have an attribute or relationship named `type` or `id`.

#### Resource objects: attributes

- [ ] The value of the `attributes` key **MUST** be an object (an "attributes object"). Members of the attributes object ("attributes") represent information about the [resource object][resource objects] in which it's defined.
- [ ] Attributes may contain any valid JSON value.
- [ ] Complex data structures involving JSON objects and arrays are allowed as attribute values. However, any object that constitutes or is contained in an attribute **MUST NOT** contain a `relationships` or `links` member, as those members are reserved by this specification for future use.
- [ ] Although has-one foreign keys (e.g. `author_id`) are often stored internally alongside other information to be represented in a resource object, these keys **SHOULD NOT** appear as attributes.

#### Resource objects: relationships

- [ ] The value of the `relationships` key **MUST** be an object (a "relationships object"). Members of the relationships object ("relationships") represent references from the [resource object][resource objects] in which it's defined to other resource objects.
- [ ] Relationships may be to-one or to-many.

#### Resource objects: relationships: relationship objects  

- [ ] A "relationship object" **MUST** contain at least one of the following:
    - `links`: a [links object][links] containing at least one of the following:
        - `self`: a link for the relationship itself (a "relationship link"). This link allows the client to directly manipulate the relationship. For example, removing an `author` through an `article`'s relationship URL would disconnect the person from the `article` without deleting the `people` resource itself.  When fetched successfully, this link returns the [linkage][resource linkage] for the related resources as its primary data.  (See [Fetching Relationships](#fetching-relationships).)
        - `related`: a [related resource link]
    - `data`: [resource linkage]
    - `meta`: a [meta object][meta] that contains non-standard meta-information about the relationship.
- [ ] A relationship object that represents a to-many relationship **MAY** also contain [pagination] links under the `links` member, as described below. Any [pagination] links in a relationship object **MUST** paginate the relationship data, not the related resources.

> Note: See [fields] and [member names] for more restrictions on this container.

#### Resource objects: relationships: related resource links

- [ ] If present, a related resource link **MUST** reference a valid URL, even if the relationship isn't currently associated with any target resources. Additionally, a related resource link **MUST NOT** change because its relationship's content changes.

#### Resource objects: linkage

- [ ] Resource linkage **MUST** be represented as one of the following:
     - `null` for empty to-one relationships.
     - an empty array (`[]`) for empty to-many relationships.
     - a single [resource identifier object] for non-empty to-one relationships.
     - an array of [resource identifier objects][resource identifier object] for non-empty to-many relationships.

#### Resource objects: links

- [ ] If present, this links object **MAY** contain a `self` [link][links] that identifies the resource represented by the resource object.
- [ ] A server **MUST** respond to a `GET` request to the specified URL with a response that includes the resource as the primary data.

### Document resource identifier objects

- [ ] A "resource identifier object" **MUST** contain `type` and `id` members.
- [ ] A "resource identifier object" **MAY** also include a `meta` member, whose value is a [meta] object that contains non-standard meta-information.

### Compound documents

- [ ] To reduce the number of HTTP requests, servers **MAY** allow responses that include related resources along with the requested primary resources. Such responses are called "compound documents".
- [ ] In a compound document, all included resources **MUST** be represented as an array of [resource objects] in a top-level `included` member.
- [ ] Compound documents require "full linkage", meaning that every included resource **MUST** be identified by at least one [resource identifier object] in the same document. These resource identifier objects could either be primary data or represent resource linkage contained within primary or included resources.
- [ ] The only exception to the full linkage requirement is when relationship fields that would otherwise contain linkage data are excluded via [sparse fieldsets](#fetching-sparse-fieldsets).

> Note: Full linkage ensures that included resources are related to either the primary data (which could be [resource objects] or [resource identifier objects][resource identifier object]) or to each other.

- [ ] A [compound document] **MUST NOT** include more than one [resource object][resource objects] for each `type` and `id` pair.

### Document meta

- [ ] Where specified, a `meta` member can be used to include non-standard meta-information. The value of each `meta` member **MUST** be an object (a "meta object").

- [ ] Any members **MAY** be specified within `meta` objects.

### Document links

- [ ] Where specified, a `links` member can be used to represent links. The value of each `links` member **MUST** be an object (a "links object").
- [ ] Each member of a links object is a "link". A link **MUST** be represented as either:
    - a string containing the link's URL.
    - <a id="document-links-link-object"></a>an object ("link object") which can contain the following members:
        - `href`: a string containing the link's URL.
        - `meta`: a meta object containing non-standard meta-information about the link.

### Document JSON API object

- [ ] A JSON:API document **MAY** include information about its implementation under a top level `jsonapi` member. If present, the value of the `jsonapi` member **MUST** be an object (a "jsonapi object"). The jsonapi object **MAY** contain a `version` member whose value is a string indicating the highest JSON API version supported. This object **MAY** also contain a `meta` member, whose value is a [meta] object that contains non-standard meta-information.

