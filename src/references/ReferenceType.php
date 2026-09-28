<?php
namespace verbb\formie\references;

enum ReferenceType: string
{
    case Text = 'text';
    case Email = 'email';
    case Number = 'number';
    case Url = 'url';
    case Date = 'date';
    case Boolean = 'boolean';
    case List = 'array';
}
