<?php

/**
 * @file
 * Contains \Drupal\media_entity_twitter\Kernel\Extension\ConstraintsTest.
 */

namespace Drupal\media_entity_twitter\Kernel\Extension;

use Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetEmbedCodeConstraint;
use Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetEmbedCodeConstraintValidator;
use Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetVisibleConstraint;
use Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetVisibleConstraintValidator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests media_entity_twitter constraints.
 *
 * @group media_entity
 */
class ConstraintsTest extends UnitTestCase {

  /**
   * Tests TweetEmbedCode constraint.
   *
   * @covers \Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetEmbedCodeConstraintValidator
   * @covers \Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetEmbedCodeConstraint
   * @dataProvider embedCodeProvider
   */
  public function testTweetEmbedCodeConstraint($embed_code, $expected_violation_count) {
    // Check message in constraint.
    $constraint = new TweetEmbedCodeConstraint();
    $this->assertEquals('Not valid Tweet URL/embed code.', $constraint->message, 'Correct constraint message found.');

    $execution_context = $this->getMockBuilder('\Drupal\Core\TypedData\Validation\ExecutionContext')
      ->disableOriginalConstructor()
      ->getMock();

    if ($expected_violation_count) {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation')
        ->with($constraint->message);
    }
    else {
      $execution_context->expects($this->exactly($expected_violation_count))
        ->method('addViolation');
    }

    $validator = new TweetEmbedCodeConstraintValidator();
    $validator->initialize($execution_context);

    $data = new \stdClass();
    $data->value = $embed_code;
    $validator->validate($data, $constraint);
  }

  /**
   * Provides test data for testTweetEmbedCodeConstraint().
   */
  public function embedCodeProvider() {
    return [
      'valid tweet URL' => ['https://twitter.com/drupal8changes/status/649167396230578176', 0],
      'valid tweet embed code' => ['<blockquote class="twitter-tweet" lang="en"><p lang="en" dir="ltr">EntityChangedInterface now also defines the function setChangedTime <a href="http://t.co/1Q58UcR8OY">http://t.co/1Q58UcR8OY</a></p>&mdash; Drupal 8 Changes (@drupal8changes) <a href="https://twitter.com/drupal8changes/status/649167396230578176">September 30, 2015</a></blockquote><script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>', 0],
      'invalid URL' => ['https://drupal.org/project/media_entity_twitter', 1],
      'invalid text' => ['I want my Tweet!', 1],
      'invalid tweet URL' => ['https://twitter.com/drupal8changes/statustypo/649167396230578176', 1],
      'invalid tweet ID' => ['https://twitter.com/drupal8changes/status/aa64916739bb6230578176', 1],
    ];
  }

  /**
   * Tests TweetVisible constraint.
   *
   * @covers \Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetVisibleConstraintValidator
   * @covers \Drupal\media_entity_twitter\Plugin\Validation\Constraint\TweetVisibleConstraint
   * @dataProvider visibleProvider
   */
  public function testTweetVisibleConstraint($embed_code, $mocked_response, $violations) {
    // Check message in constraint.
    $constraint = new TweetVisibleConstraint();
    $this->assertEquals('Referenced tweet is not publicly visible.', $constraint->message, 'Correct constraint message found.');

    $http_client = $this->getMock('\GuzzleHttp\Client');
    $http_client->expects($this->once())
      ->method('__call')
      ->with('get', [$embed_code, ['allow_redirects' => FALSE]])
      ->willReturn($mocked_response);

    // Make sure no violations are raised for visible tweet.
    $execution_context = $this->getMockBuilder('\Drupal\Core\TypedData\Validation\ExecutionContext')
      ->disableOriginalConstructor()
      ->getMock();

    if ($violations) {
      $execution_context->expects($this->once())
        ->method('addViolation')
        ->with($constraint->message);
    }
    else {
      $execution_context->expects($this->exactly($violations))
        ->method('addViolation');
    }

    $validator = new TweetVisibleConstraintValidator($http_client);
    $validator->initialize($execution_context);

    $data = new \stdClass();
    $data->value = $embed_code;
    $validator->validate($data, $constraint);
  }

  /**
   * Provides test data for testTweetVisibleConstraint().
   */
  public function visibleProvider() {
    $visible_response = $this->getMock('\GuzzleHttp\Psr7\Response');
    $visible_response->expects($this->any())
      ->method('getStatusCode')
      ->will($this->returnValue(200));

    $invisible_response = $this->getMock('\GuzzleHttp\Psr7\Response');
    $invisible_response->expects($this->once())
      ->method('getStatusCode')
      ->will($this->returnValue(302));
    $invisible_response->expects($this->once())
      ->method('getHeader')
      ->with('location')
      ->will($this->returnValue(['https://twitter.com/drupal8changes?protected_redirect=true']));

    return [
      'valid URL' => ['https://twitter.com/drupal8changes/status/649167396230578176', $visible_response, 0],
      'invalid URL' => ['https://twitter.com/drupal8changes/status/649637310024273920', $invisible_response, 1],
    ];
  }

}
