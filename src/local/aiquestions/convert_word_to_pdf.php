<?php 

 use PhpOffice\PhpWord\IOFactory;
 use PhpOffice\PhpWord\Settings;
 use Aiquestions\Configs\convert_config;

function convert_word_to_pdf($inputPath, $outputPath) {
   try {
    global $CFG;
    require_once($CFG->dirroot . '/vendor/autoload.php');

        $pdfRendererPath = convert_config::$pdfRendererPath;
        $phpWord = IOFactory::load($inputPath);

        Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
        Settings::setPdfRendererPath($pdfRendererPath);
       
        $xmlWriter = IOFactory::createWriter($phpWord, 'PDF');
        $xmlWriter->save($outputPath);

        return file_exists($outputPath); 
    } catch (Exception $error) 
        {
            error_log($error->getMessage());
            return false;
        }
 }
