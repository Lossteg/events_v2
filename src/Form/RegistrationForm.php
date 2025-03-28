<?php

declare(strict_types=1);

namespace Drupal\events_v2\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\events_v2\Enums\EventFields;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Simple test form for event registration.
 */
class RegistrationForm extends FormBase {

  const FORM_ID = 'events_registration_form';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return self::FORM_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, NodeInterface $node = NULL): array {
    $form['#prefix'] = '<div id="submit-button-wrapper">';
    $form['#suffix'] = '</div>';

    $form_state->set('node', $node);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register to event'),
      '#ajax' => [
        'callback' => '::ajaxSubmitForm',
        'wrapper' => 'submit-button-wrapper',
        'effect' => 'fade',
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback for form submission.
   */
  public function ajaxSubmitForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $response->addCommand(new MessageCommand(
      $this->t('You have been successfully registered for the event'),
      null,
      ['type' => 'status']
    ));

    $response->addCommand(new CssCommand(
      '#submit-button-wrapper',
      [
        'pointer-events' => 'none',
        'cursor' => 'pointer',
        'opacity' => '0.5',
      ]
    ));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $node = $form_state->get('node');
    $current_user = User::load($this->currentUser()->id());

    try {
      $node->get(EventFields::PARTICIPANTS)->appendItem($current_user);
      $node->save();
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError(
        $this->t(
          'Registration failed: @error', ['@error' => $e->getMessage()]
        )
      );
    }
  }

}
