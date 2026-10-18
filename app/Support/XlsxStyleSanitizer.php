<?php

namespace App\Support;

use ZipArchive;

/**
 * Alguns ficheiros .xlsx exportados por sistemas externos (ex.: exports
 * hospitalares) omitem o atributo "numFmtId" em entradas <xf> do
 * xl/styles.xml. Isso é válido na spec OOXML (equivale a numFmtId="0"),
 * mas faz o openspout (usado pelo spatie/simple-excel) rebentar com:
 * "StyleManager::isNumFmtIdBuiltInDateFormat(): Argument #1 ($numFmtId)
 * must be of type int, null given".
 *
 * Esta classe normaliza o styles.xml do ficheiro, adicionando
 * numFmtId="0" onde faltar, antes de o ficheiro ser lido.
 */
class XlsxStyleSanitizer
{
    public static function sanitize(string $path): void
    {
        $zip = new ZipArchive();

        if (true !== $zip->open($path)) {
            return;
        }

        $stylesXml = $zip->getFromName('xl/styles.xml');

        if (false === $stylesXml) {
            $zip->close();

            return;
        }

        // Algumas ferramentas geram o styles.xml com um prefixo de namespace
        // (ex.: "<x:xf ... />" em vez de "<xf ... />"), por isso o prefixo
        // é opcional e preservado.
        $fixed = preg_replace_callback(
            '/<([a-zA-Z0-9]+:)?xf\b[^>]*>/',
            static function (array $matches): string {
                $tag = $matches[0];

                if (str_contains($tag, 'numFmtId=')) {
                    return $tag;
                }

                $prefix = $matches[1] ?? '';

                return preg_replace('/^<'.$prefix.'xf\b/', '<'.$prefix.'xf numFmtId="0"', $tag, 1);
            },
            $stylesXml
        );

        if ($fixed !== $stylesXml) {
            $zip->addFromString('xl/styles.xml', $fixed);
        }

        $zip->close();
    }
}
