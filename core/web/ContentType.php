<?php

declare(strict_types=1);

namespace core\web;

/**
 * This determines how to convert data into response content.
 *
 * - FORMAT_RAW: the data will be treated as the response content without any conversion.
 *   No extra HTTP header will be added.
 * - FORMAT_HTML: the data will be treated as the response content without any conversion.
 *   The "Content-Type" header will set as "text/html".
 * - FORMAT_JSON: the data will be converted into JSON format, and the "Content-Type"
 *   header will be set as "application/json".
 * - FORMAT_JSONP: the data will be converted into JSONP format, and the "Content-Type"
 *   header will be set as "text/javascript". Note that in this case `$data` must be an array
 *   with "data" and "callback" elements. The former refers to the actual data to be sent,
 *   while the latter refers to the name of the JavaScript callback.
 * - FORMAT_XML: the data will be converted into XML format.
 */

class ContentType
{
    const FORMAT_HTML   = 'html';
    const FORMAT_JSON   = 'json';
    // const FORMAT_JSONP  = 'jsonp';
    const FORMAT_XML    = 'xml';
    const FORMAT_RAW    = 'raw';
}