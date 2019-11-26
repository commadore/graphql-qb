<?php

namespace Tests\Commadore\GraphQL;

use Commadore\GraphQL\Enum;
use Commadore\GraphQL\Fragment;
use Commadore\GraphQL\Operation;
use Commadore\GraphQL\Query;
use PHPUnit\Framework\TestCase;

class QueryTest extends TestCase
{
    /**
     * Tests adding fields and args using constructor parameters or method call.
     */
    public function testAddFields()
    {
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

        $operation2 = (new Operation(Query::KEYWORD, 'article', [],
            ['article' => (new Query())
            ->arguments([
                'id' => 999,
                'title' => 'Hello World',
                'note' => 3.5,
            ])
            ->fields([
                'id',
                'title',
                'body',
            ])
        ]
    ));

        $expected =
            'query article {
  article: article(id: 999, note: 3.5, title: "Hello World") {
    body
    id
    title
  }
}
';
        $this->assertEquals($expected, (string) $operation);
        $this->assertEquals($expected, (string) $operation2);
    }

    public function testComplexArguments()
    {
      $operation = new Operation(Query::KEYWORD, 'article');
      $query1 = new Query('article', [
          'id' => 999,
          'enum' => new Enum('e'),
          'intValues' => [10, 20],
          'strValues' => ['xxx', 'yyy'],
          'asoc' => [
              'id' => 999,
              'title' => 'Hello World',
              'note' => 3.5,
              'intSubValues' => [30, 40],
              'strSubValues' => ['aaa', 'bbb'],
          ]
      ], [
          'id',
          'title',
          'body',
      ]);
      $operation->fields(['article' => $query1]);

      $expected =
          'query article {
  article: article(asoc: {id: 999, title: "Hello World", note: 3.5, intSubValues: [30, 40], strSubValues: ["aaa", "bbb"]}, enum: e, id: 999, intValues: [10, 20], strValues: ["xxx", "yyy"]) {
    body
    id
    title
  }
}
';

      $this->assertEquals($expected, (string) $operation);
    }

    /**
     * Tests the order of fields and arguments.
     */
    public function testSortFields()
    {
        $operation = new Operation(Query::KEYWORD, 'article');
        $query1 = (new Query('article'))
            ->arguments([
                'title' => 'Hello World',
                'note' => 3.5,
                'id' => 999,
            ])
            ->fields([
                'id',
                'title',
                'body',
            ]);
        $operation->fields(['article' => $query1]);
        $query2 = (new Query('article'))
            ->arguments([
                'id' => 999,
                'title' => 'Hello World',
                'note' => 3.5,
            ])
            ->fields([
                'title',
                'id',
                'body',
            ]);

        $this->assertEquals((string) $query1, (string) $query2);
    }

    /**
     * Tests field alias.
     */
    public function testAlias()
    {
        $operation = new Operation(Query::KEYWORD, 'article');
        $query = (new Query('article'))->fields([
            'articleId' => 'id',
            'articleTitle' => 'title',
            'body',
        ]);
        $operation->fields(['article' => $query]);
        $expected =
            'query article {
  article: article {
    articleId: id
    articleTitle: title
    body
  }
}
';
        $this->assertEquals($expected, (string) $operation);
    }

    /**
     * Tests operation name generation and printing.
     */
    public function testOperationName()
    {
        $operation = new Operation(Query::KEYWORD, 'articlesQuery');
        $operation->variables([
            '$id' => 'Integer',
        ]);

        $query2 = (new Query())
            ->arguments([
                'id' => '$id',
            ])
            ->fields([
                'id',
                'title',
                'body',
            ]);

        $expected2 =
            'query articlesQuery($id: Integer) {
  article: article(id: $id) {
    body
    id
    title
  }
}
';
        $operation->fields(['article' => $query2]);
        $this->assertEquals($expected2, (string) $operation);

        $operation = new Operation(Query::KEYWORD, 'articlesQuery');
        // query with only operation name
        $query3 = (new Query('article'))
            ->operationName('articlesQuery')
            ->fields([
                'id',
                'title',
                'body',
            ]);

        $expected3 =
            'query articlesQuery {
  article: article {
    body
    id
    title
  }
}
';
        $operation->fields(['article' => $query3]);
        $this->assertEquals($expected3, (string) $operation);
    }

    public function testComplexArgumentsOperationName()
    {
        $operation = new Operation(Query::KEYWORD, 'articlesQuery');
        $operation->variables([
            '$id1' => 'Integer',
            '$id2' => 'Integer',
        ]);

        $query1 = new Query('article', [
            'id' => '$id1',
            'asoc' => [
                'id' => '$id2',
            ]
        ], [
            'id',
            'title',
            'body',
        ]);
        $operation->fields(['article' => $query1]);

        $expected =
            'query articlesQuery($id1: Integer, $id2: Integer) {
  article: article(asoc: {id: $id2}, id: $id1) {
    body
    id
    title
  }
}
';

        $this->assertEquals($expected, (string) $operation);
    }

    /**
     * Tests directives printing.
     */
    public function testDirective()
    {
        $operation = new Operation(Query::KEYWORD, 'articlesQuery');
        $operation->variables([
            '$withoutTags' => 'Boolean',
        ]);

        // skip if directive
        $query1 = (new Query())

            ->fields([
                'id',
                'title',
                'body',
                'tags',
            ])
            ->skipIf([
                'tags' => '$withoutTags',
            ])
        ;

        $expected1 =
            'query articlesQuery($withoutTags: Boolean) {
  article: article {
    body
    id
    tags @skip(if: $withoutTags)
    title
  }
}
';
        $operation->fields(['article' => $query1]);
        $this->assertEquals($expected1, (string) $operation);

        // include if directive
        $query2 = (new Query('article'))
            ->operationName('articlesQuery')
            ->fields([
                'id',
                'title',
                'body',
                'tags',
            ])
            ->includeIf([
                'tags' => '$withTags',
            ])
        ;

        $expected2 =
            'query articlesQuery($withTags: Boolean!) {
  article: article {
    body
    id
    tags @include(if: $withTags)
    title
  }
}
';
        $operation = new Operation(Query::KEYWORD, 'articlesQuery');
        $operation->variables([
            '$withTags' => 'Boolean!',
        ]);
        $operation->fields(['article' => $query2]);
        $this->assertEquals($expected2, (string) $operation);
    }

    public function testQueryWithFragment()
    {
        $operation = new Operation(Query::KEYWORD, 'articlesQuery');
        $query = (new Query('article'));
        $query->operationName('articlesQuery');
        $query->fields([
                'id',
                'title',
                'body',
                '...imageFragment',
            ]);
        $operation->addFragment(new Fragment('imageFragment', 'image', [
                'height',
                'width',
                'filename',
                'size',
                'formats' => (new Query())->fields([
                    'id',
                    'name',
                    'url',
                ]),
            ]))
        ;
        $operation->fields(['article' => $query]);
        $expected =
            'query articlesQuery {
  article: article {
    ...imageFragment
    body
    id
    title
  }
}

fragment imageFragment on image {
  filename
  formats {
    id
    name
    url
  }
  height
  size
  width
}
';
        $this->assertEquals($expected, (string) $operation);
    }

    public function testQueryWithoutAliases()
    {
        $operation = new Operation(
            Query::KEYWORD,
            'test',
            [],
            [
                'myAlias' => new Query(
                    'myAliasedField',
                    [],
                    [
                        'subfieldA'
                    ]
                ),
                new Query(
                    'unaliasedField',
                    [],
                    [
                        'nosuchsubfield'
                    ]
                )
            ]
        );
        $expected = 'query test {
  myAlias: myAliasedField {
    subfieldA
  }
  unaliasedField {
    nosuchsubfield
  }
}
';
       $this->assertEquals($expected, (string) $operation);
    }

}
