<?php

namespace Tests\Commadore\GraphQL;

use Commadore\GraphQL\Mutation;
use Commadore\GraphQL\Operation;
use PHPUnit\Framework\TestCase;

class MutationTest extends TestCase
{
    public function testMutation()
    {
        $mutation = new Operation(Mutation::KEYWORD, 'CreateReviewForEpisode');
        $mutation
            ->variables(
                [
                    '$ep' => 'Episode!',
                    '$review' => 'ReviewInput!',
                ]
            )
            ->fields(['createReview' => (new Mutation())
                ->arguments(
                    [
                        'episode' => '$ep',
                        'review' => '$review',
                    ]
                )
                ->fields(
                    [
                        'stars',
                        'commentary',
                    ]
                )
            ]);

        $expected =
            'mutation CreateReviewForEpisode($ep: Episode!, $review: ReviewInput!) {
  createReview: createReview(episode: $ep, review: $review) {
    commentary
    stars
  }
}
';
        $this->assertEquals($expected, (string) $mutation);
    }
}
