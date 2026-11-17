<?php

namespace App\Constants;

final readonly class SiegEndpoints
{
    const string CREATE_JWT_TOKEN = '/api/v1/create-jwt';
    const string SEND_XML = '/api/v1/send-xml';
    const string COUNT_XML = '/api/v1/contar-xmls';
    const string DOWNLOAD_XML = '/api/v1/baixar-xml';
}
