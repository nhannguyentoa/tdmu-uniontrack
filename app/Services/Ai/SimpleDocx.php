<?php

namespace App\Services\Ai;

use RuntimeException;
use ZipArchive;

/**
 * Tạo tệp .docx tối giản (Times New Roman 13, giãn dòng 1.3) từ văn bản dùng ký hiệu của trợ lý soạn thảo:
 * "# " tiêu đề căn giữa, "## " tiêu đề mục, "- " gạch đầu dòng, còn lại là đoạn văn. Không cần thư viện ngoài.
 */
final class SimpleDocx
{
    public static function build(string $text): string
    {
        $body = '';

        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = rtrim($line);

            if (trim($line) === '') {
                continue;
            }

            if (str_starts_with($line, '# ')) {
                $body .= self::paragraph(substr($line, 2), bold: true, center: true, size: 28, after: 200);
            } elseif (str_starts_with($line, '## ')) {
                $body .= self::paragraph(substr($line, 3), bold: true, before: 160, after: 80);
            } elseif (str_starts_with($line, '- ')) {
                $body .= self::paragraph('– '.substr($line, 2), indent: 360);
            } else {
                $body .= self::paragraph($line, firstLine: 567);
            }
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1701" w:header="720" w:footer="720" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Times New Roman" w:hAnsi="Times New Roman" w:cs="Times New Roman" w:eastAsia="Times New Roman"/><w:sz w:val="26"/><w:szCs w:val="26"/><w:lang w:val="vi-VN"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr><w:spacing w:after="80" w:line="312" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            .'</w:styles>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'</Relationships>';

        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';

        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Không tạo được tệp Word.');
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);
        $zip->close();

        $bytes = (string) file_get_contents($path);
        @unlink($path);

        return $bytes;
    }

    private static function paragraph(string $text, bool $bold = false, bool $center = false, int $size = 26, int $before = 0, int $after = 80, int $indent = 0, int $firstLine = 0): string
    {
        $pPr = '<w:pPr>'
            .'<w:spacing w:before="'.$before.'" w:after="'.$after.'"/>'
            .($indent || $firstLine ? '<w:ind w:left="'.$indent.'" w:firstLine="'.$firstLine.'"/>' : '')
            .($center ? '<w:jc w:val="center"/>' : '<w:jc w:val="both"/>')
            .'</w:pPr>';

        $rPr = '<w:rPr>'.($bold ? '<w:b/>' : '').'<w:sz w:val="'.$size.'"/><w:szCs w:val="'.$size.'"/></w:rPr>';
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<w:p>'.$pPr.'<w:r>'.$rPr.'<w:t xml:space="preserve">'.$escaped.'</w:t></w:r></w:p>';
    }
}
