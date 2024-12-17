<?php

namespace Drupal\lob_skos\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\lob_skos\Services\FileManager;
use Drupal\lob_skos\Services\SkosExporter;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExportForm extends FormBase
{
  protected FileManager $fileManager;

  protected SkosExporter $exporter;

  public function __construct(FileManager $fileManager, SkosExporter $exporter)
  {
    $this->fileManager = $fileManager;
    $this->exporter = $exporter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ExportForm
  {
    // Instantiates this form class.
    return new static(
      $container->get(FileManager::class),
      $container->get(SkosExporter::class)
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
    return 'lob_skos_export_form';
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
    $targetDirectory = $this->fileManager->getExportDirectoryUrl();
    $targetFile = $this->fileManager->getExportFileName($targetDirectory, 'lob-' . date('Y-m-d'), '.ttl');

    $form['links'] = [
      '#type' => 'markup',
      '#markup' => '<div class="text-right">' . $this->t('Export') . ' | <a href="publish"> ' . $this->t('Publish') . '</a></div>',
    ];

    $form['description'] = array(
      '#type' => 'markup',
      '#markup' => t('<p>Export the thesaurus into RDF format.</p>'),
    );

    $form['directory'] = array(
      '#type' => 'hidden',
      '#required' => TRUE,
      '#value' => $targetDirectory
    );

    $form['file'] = array(
      '#type' => 'textfield',
      '#title' => 'Target File',
      '#required' => TRUE,
      '#value' => $targetFile,
      '#description' => t('Enter the name of the file to be generated.'),
    );

    $form['syntax'] = array(
      '#type' => 'hidden',
      '#required' => TRUE,
      '#value' => 'turtle'
    );

    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
    ];

    // If there are no nodes, prevent form submission. Find out if
    // we have a node to work with. Otherwise it won't work.
    $n = Drupal::entityQuery('node')->accessCheck()->condition('type', 'concept')->count()->execute();

    if ($n == 0) {
      $form['actions']['submit']['#disabled'] = TRUE;

      $this->messenger()->addStatus("There are no entities of type 'concept' to be exported.");
    }

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

    $targetDirectory = $form_state->getValue('directory');

    if (!$this->fileManager->isValidExportDirectoryUrl($targetDirectory)) {
      $form_state->setErrorByName('directory', $this->t('The target directory must be public://'));
    }

    $targetFile = $form_state->getValue('file');

    if (!$this->fileManager->isValidExportFileName($targetFile)) {
      $form_state->setErrorByName('file', $this->t('Please enter a valid file name.'));
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
    // Get the export parameters from the form.
    $syntax = $form_state->getValue('syntax');
    $directory = $form_state->getValue('directory');
    $file = $form_state->getValue('file');
    $file_url = $this->fileManager->getPublicFileUrl($directory, $file);
    $file_path = $this->fileManager->getLocalFilePath($directory, $file);

    try {
      $this->exporter->export($file_path, $syntax);

      // Give helpful information about how many nodes are being operated on.
      $msg = Markup::create("<h4>Export successful</h4>"
        ."<b>URL:</b><pre><a href='$file_url' target='_blank'>$file_url</a></pre>"
        ."<b>Size:</b><pre>".round(filesize($file_path) / 1024)." kB</pre>"
        ."<b>Concepts:</b><pre>".$this->exporter->getConceptCount()."</pre>"
        ."<b>Labels:</b><pre>".$this->exporter->getLabelCount()."</pre>");

      $this->messenger()->addStatus($msg);
    } catch (Exception $ex) {
      $this->messenger()->addError($ex->getMessage());
    }
  }
}
