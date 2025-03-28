<?php

declare(strict_types=1);

namespace Drupal\events_v2\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\events_v2\Enums\WeatherApiCredentials;

/**
 * Provides a configuration form for event settings.
 */
class EventSettingsForm extends ConfigFormBase {

  const FORM_ID = 'events_settings_form';
  const CONFIG_NAME = ['events.settings'];

  /**
   * Returns configuration names for the form.
   *
   * @return array
   *   Array containing configuration names used in this form.
   */
  protected function getEditableConfigNames(): array {
    return self::CONFIG_NAME;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId(): string {
    return self::FORM_ID;
  }

  /**
   * Builds the configuration form for weather API settings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(WeatherApiCredentials::CONFIG);

    $form['weather_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weather API Key'),
      '#default_value' => $config->get(WeatherApiCredentials::KEY),
      '#required' => TRUE,
    ];

    $form['weather_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weather API URL'),
      '#default_value' => $config->get(WeatherApiCredentials::URL),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Processes and saves the form submission values.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->configFactory()->getEditable(WeatherApiCredentials::CONFIG);

    $config->set(
      WeatherApiCredentials::KEY,
      $form_state->getValue(WeatherApiCredentials::KEY)
    );
    $config->set(
      WeatherApiCredentials::URL,
      $form_state->getValue(WeatherApiCredentials::URL)
    );
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
