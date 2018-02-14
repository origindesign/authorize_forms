<?php

/**
 * @file
 * Contains \Drupal\authorize_forms\Form\AuthorizeNetSettingsForm.
 */

namespace Drupal\authorize_forms\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * authorize.net form.
 */
class AuthorizeNetSettingsForm extends ConfigFormBase {


    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [
            'authorize_forms.settings',
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'authorize_forms_settings_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {

        $config = $this->config('authorize_forms.settings');
        
        $form['api_login_id'] = array(
            '#type' => 'textfield',
            '#title' => t('API Login ID'),
            '#required' => TRUE,
            '#default_value' => $config->get('api_login_id')
        );

        $form['public_client_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Public Client Key'),
            '#required' => TRUE,
            '#default_value' => $config->get('public_client_key')
        );

        $form['transaction_key'] = array(
            '#type' => 'textfield',
            '#title' => t('Transaction Key'),
            '#required' => TRUE,
            '#default_value' => $config->get('transaction_key')
        );

        return parent::buildForm($form, $form_state);
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        // Get form values
        $form_values = $form_state->getValues();

        // Check required fields
        if ($form_values['api_login_id'] == '') {
            $form_state->setErrorByName('api_login_id', $this->t('The API Login ID field is required'));
        }
        if ($form_values['public_client_key'] == '') {
            $form_state->setErrorByName('public_client_key', $this->t('The Public Client Key field is required'));
        }
        if ($form_values['transaction_key'] == '') {
            $form_state->setErrorByName('transaction_key', $this->t('The Transaction Key field is required'));
        }

    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {

        // Get form values
        $form_values = $form_state->getValues();

        // Set config from from values
        $this->config('authorize_forms.settings')
            ->set('api_login_id', $form_values['api_login_id'])
            ->set('public_client_key', $form_values['public_client_key'])
            ->set('transaction_key', $form_values['transaction_key'])
            ->save();

        parent::submitForm($form, $form_state);

    }

}