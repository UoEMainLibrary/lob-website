<?php

namespace Drupal\lob_skos\Form;

require __DIR__ . '/../vocabularies.php';

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lob_skos\Services\FileManager;
use Drupal\lob_skos\Services\VirtuosoAdapter;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PublishForm extends FormBase
{
  protected FileManager $fileManager;

  protected VirtuosoAdapter $virtuosoAdapter;

  public function __construct(FileManager $fileManager, VirtuosoAdapter $virtuosoAdapter)
  {
    $this->fileManager = $fileManager;
    $this->virtuosoAdapter = $virtuosoAdapter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): PublishForm
  {
    // Instantiates this form class.
    return new static(
      $container->get(FileManager::class),
      $container->get(VirtuosoAdapter::class)
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId(): string
  {
    return 'lob_skos_publish_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $sourceDirectory = $this->fileManager->getExportDirectoryUrl();
    $sourceFiles = $this->fileManager->getExportedFiles($sourceDirectory);

    krsort($sourceFiles);

    $options = [];
    $selected = NULL;

    if (count($sourceFiles) > 0) {
      $selected = array_values($sourceFiles)[0]->filename;
    }

    foreach ($sourceFiles as $path => $file) {
      $key = $this->fileManager->getLocalFilePath($path);

      $options[$key] = $file->filename;
    }

    $form['links'] = [
      '#type' => 'markup',
      '#markup' => '<div class="text-right"><a href="export">' . $this->t('Export') . '</a> | ' . $this->t('Publish') . '</div>',
    ];

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<p>Publish the thesaurus into a SPARQL endpoint.</p>'),
    ];

    $form['file'] = [
      '#type' => 'select',
      '#title' => $this->t('Source File'),
      '#options' => $options,
      '#default_value' => $selected,
      '#required' => TRUE,
      '#description' => $this->t('Select the name of the file to be published.'),
    ];

    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#size' => 20,
      '#required' => TRUE,
      '#description' => $this->t('Enter the username.'),
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 20,
      '#required' => TRUE,
      '#description' => $this->t('Enter the password.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Publish'),
    ];

    return $form;
  }

  /**
   * Validate the form.
   *
   * @param array $form
   * @param FormStateInterface $form_state
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    parent::validateForm($form, $form_state);

    $file = $form_state->getValue('file');

    if (empty($file)) {
      $form_state->setErrorByName('file', $this->t('Invalid file name selected.'));
    }

    $username = $form_state->getValue('username');

    if (empty($username)) {
      $form_state->setErrorByName('username', $this->t('Please enter your username.'));
    }

    $password = $form_state->getValue('password');

    if (empty($password)) {
      $form_state->setErrorByName('password', $this->t('Please enter your password.'));
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $username = $form_state->getValue('username');
    $password = $form_state->getValue('password');

    $this->virtuosoAdapter->connect('VOS', $username, $password);

    if ($this->virtuosoAdapter->isConnected()) {
      try {
        $file = $form_state->getValue('file');

        $graph_uri = lob();

        $this->virtuosoAdapter->executeQuery("DROP SILENT GRAPH <$graph_uri>");
        $this->virtuosoAdapter->executeQuery("CREATE GRAPH <$graph_uri>");
        $this->virtuosoAdapter->executeQuery("LOAD <file://$file> INTO <$graph_uri>");

        $msg = Markup::create("<h4>Upload successful</h4>"
          . "<b>Graph:</b><pre>$graph_uri</pre>"
          . "<b>Source:</b><pre>$file</pre>");

        $this->messenger()->addStatus($msg);
      } catch (Exception $ex) {
        $this->messenger()->addError($ex->getMessage());
      }
    } else {
      $this->messenger()->addError("Unable to connect to SPARQL endpoint. Please check your username and password.");
    }
  }
}
