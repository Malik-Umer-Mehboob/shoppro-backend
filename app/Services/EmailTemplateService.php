<?php

namespace App\Services;

use App\Models\EmailTemplate;

class EmailTemplateService
{
    public function createTemplate(array $data)
    {
        return EmailTemplate::create($data);
    }

    public function updateTemplate($templateId, array $data)
    {
        $template = EmailTemplate::findOrFail($templateId);
        $template->update($data);
        return $template;
    }

    public function deleteTemplate($templateId)
    {
        $template = EmailTemplate::findOrFail($templateId);
        return $template->delete();
    }

    public function getTemplateByName($name)
    {
        return EmailTemplate::where('name', $name)->where('is_active', true)->first();
    }

    public function parseTemplate($content, array $data)
    {
        foreach ($data as $key => $value) {
            $content = str_replace("{{ $key }}", $value, $content);
        }
        return $content;
    }
}
