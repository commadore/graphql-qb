## Refactored version of https://github.com/fnash/graphql-qb.
I have a use case where I need to mock this lib, so statics and traits aren't desirable

# graphql-qb
A php GraphQL Query Builder. Nice API. Readable queries. Examples in Unit Tests.

Includes:
- Query / Mutation / Fragment
- Sorted Fields
- Mandatory Operation Object
- Add variables
- Add arguments
- Directives (Include / Skip)
- Sub query

TODO:
- Arguments in sub queries


```php
<?php

include_once 'vendor/autoload.php';

use Commadore\GraphQL\Operation;
use Commadore\GraphQL\Query;

        $operation = new Operation(Query::KEYWORD, 'article');
        $query1 = new Query('article', [
            'id' => 999,
            'title' => 'Hello World',
            'note' => 3.5,
        ], [
            'id',
            'title',
            'body',
        ]);
        $operation->fields(['article' => $query1]);

        echo $operation;

```

```graphql
query article {
  article: article(id: 999, note: 3.5, title: "Hello World") {
    body
    id
    title
  }
}
```
