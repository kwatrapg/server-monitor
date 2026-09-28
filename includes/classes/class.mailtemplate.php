<?php

// Designed HTML emails: incident alerts and the scheduled health report.
//
// Email clients (Outlook especially) ignore <style> blocks, flexbox and most
// modern CSS, so everything here is nested tables with inline styles only. Every
// piece of dynamic text goes through self::h() - asset names, command output and
// alert values are user- or agent-supplied.
class MailTemplate {

    const FONT = "font-family:-apple-system,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;";

    const COLORS = [
        "ok"      => ["accent" => "#15803d", "soft" => "#dcfce7", "text" => "#166534", "dot" => "#16a34a"],
        "warning" => ["accent" => "#b45309", "soft" => "#fef3c7", "text" => "#92400e", "dot" => "#f59e0b"],
        "error"   => ["accent" => "#991b1b", "soft" => "#fee2e2", "text" => "#991b1b", "dot" => "#dc2626"],
        "unknown" => ["accent" => "#475569", "soft" => "#e2e8f0", "text" => "#334155", "dot" => "#94a3b8"],
    ];


    public static function h($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private static function company() {
        $company = getConfigValue("company_name");
        return ($company === "" || $company === null) ? "Sentruo" : $company;
    }

    // Absolute link into the app, or "" when no app_url is configured (a relative
    // link is useless in an email, so the button is left out instead).
    private static function appLink($path) {
        $base = (string) getConfigValue("app_url");
        if ($base === "") return "";
        return rtrim($base, "/") . "/" . ltrim($path, "/");
    }

    // Maps the app's numeric status (1 ok, 2 warning, 3 alert, else unknown).
    public static function statusKey($status) {
        if ($status == 1) return "ok";
        if ($status == 2) return "warning";
        if ($status == 3) return "error";
        return "unknown";
    }

    private static function pill($key, $label) {
        $c = self::COLORS[$key];
        return '<span style="display:inline-block;padding:2px 8px;border-radius:4px;background:' . $c['soft'] . ';color:' . $c['text'] . ';font-size:11px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;">' . self::h($label) . '</span>';
    }

    private static function dot($key) {
        return '<span style="display:inline-block;width:10px;height:10px;border-radius:5px;background:' . self::COLORS[$key]['dot'] . ';"></span>';
    }

    private static function chip($text) {
        return '<span style="display:inline-block;margin:4px 4px 0 0;padding:2px 6px;border-radius:3px;background:#f1f5f9;border:1px solid #e2e8f0;color:#475569;font-size:11px;font-family:Consolas,Menlo,monospace;">' . self::h($text) . '</span>';
    }

    private static function button($href, $label, $color) {
        if ($href === "") return "";
        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top:20px;"><tr><td bgcolor="' . $color . '" style="border-radius:6px;">'
            . '<a href="' . self::h($href) . '" style="display:inline-block;padding:11px 22px;' . self::FONT . 'font-size:14px;font-weight:600;color:#ffffff;text-decoration:none;border-radius:6px;">' . self::h($label) . ' &rarr;</a>'
            . '</td></tr></table>';
    }

    private static function sectionTitle($icon, $text) {
        return '<div style="' . self::FONT . 'font-size:13px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#1e293b;margin:0 0 14px;">' . $icon . '&nbsp; ' . self::h($text) . '</div>';
    }


    // Page shell shared by every email: coloured header band, white body, footer.
    // $headerExtra is raw HTML (already escaped by the caller), e.g. a progress bar.
    private static function layout($accent, $eyebrow, $title, $subtitle, $headerExtra, $body, $preheader) {
        $company = self::h(self::company());
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . self::h($title) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f1f5f9;">'
            // hidden preview text shown next to the subject in most inboxes
            . '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . self::h($preheader) . '</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f5f9"><tr><td align="center" style="padding:24px 12px;">'
            . '<table role="presentation" width="640" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:640px;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0;">'
            . '<tr><td bgcolor="' . $accent . '" style="padding:28px 28px 24px;background:' . $accent . ';' . self::FONT . 'color:#ffffff;">'
                . '<div style="font-size:11px;letter-spacing:1.6px;text-transform:uppercase;opacity:.8;">' . self::h($eyebrow) . '</div>'
                . '<div style="font-size:22px;font-weight:700;line-height:1.3;margin-top:8px;">' . $title . '</div>'
                . '<div style="font-size:13px;opacity:.85;margin-top:6px;">' . self::h($subtitle) . '</div>'
                . $headerExtra
            . '</td></tr>'
            . $body
            . '<tr><td style="padding:18px 28px;background:#f8fafc;border-top:1px solid #e2e8f0;' . self::FONT . 'font-size:12px;color:#94a3b8;line-height:1.6;">'
                . sprintf(self::h(__('This is an automated message from %s monitoring.')), '<strong style="color:#64748b;">' . $company . '</strong>')
                . ' ' . self::h(__('Manage notifications from Settings in your dashboard.'))
            . '</td></tr>'
            . '</table></td></tr></table></body></html>';
    }


    // ----------------------------------------------------------------------------------------------
    // INCIDENT ALERT
    // ----------------------------------------------------------------------------------------------

    // $action: open | unresolved | close. $assettype is the translated label
    // ("Server", "Website"...), $link an app-relative path to the asset.
    public static function incident($action, $assettype, $assetname, $typestring, $contactname, $link) {
        $states = [
            "open"       => ["error",   "&#128680;", __('Incident Opened'),     __('A monitoring alert has been triggered and needs attention.')],
            "unresolved" => ["warning", "&#9888;&#65039;", __('Incident Still Unresolved'), __('This incident is still open. This is a scheduled reminder.')],
            "close"      => ["ok",      "&#9989;",  __('Incident Resolved'),   __('The condition has cleared and the incident was closed automatically.')],
        ];
        [$key, $icon, $heading, $lead] = $states[$action] ?? $states["open"];
        $c = self::COLORS[$key];
        $statusLabel = [ "error" => __('Open'), "warning" => __('Open'), "ok" => __('Resolved') ][$key];

        $rows = [
            __('Asset')     => '<strong>' . self::h($assetname) . '</strong> <span style="color:#64748b;">(' . self::h($assettype) . ')</span>',
            __('Condition') => self::h($typestring !== '' ? $typestring : '-'),
            __('Status')    => self::pill($key, $statusLabel),
            __('Time')      => self::h(dateTimeDisplay(date('Y-m-d H:i:s'))),
        ];
        $table = '';
        foreach ($rows as $label => $value) {
            $table .= '<tr>'
                . '<td style="padding:11px 14px;border-bottom:1px solid #eef2f7;' . self::FONT . 'font-size:12px;font-weight:600;letter-spacing:.4px;text-transform:uppercase;color:#64748b;width:120px;vertical-align:top;">' . self::h($label) . '</td>'
                . '<td style="padding:11px 14px;border-bottom:1px solid #eef2f7;' . self::FONT . 'font-size:14px;color:#0f172a;">' . $value . '</td>'
                . '</tr>';
        }

        $body = '<tr><td style="padding:26px 28px 30px;' . self::FONT . 'color:#334155;font-size:14px;line-height:1.6;">'
            . '<p style="margin:0 0 6px;">' . self::h(__('Hello')) . ' ' . self::h($contactname) . ',</p>'
            . '<p style="margin:0 0 20px;">' . self::h($lead) . '</p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-left:4px solid ' . $c['dot'] . ';border-radius:6px;border-collapse:separate;">' . $table . '</table>'
            . self::button(self::appLink($link), __('View in dashboard'), $c['accent'])
            . '</td></tr>';

        return self::layout(
            $c['accent'],
            self::company() . ' · ' . __('Monitoring Alert'),
            $icon . ' ' . self::h($heading) . ' &mdash; ' . self::h($assetname),
            $assettype . ' · ' . $typestring,
            '',
            $body,
            $heading . ': ' . $assettype . ' ' . $assetname . ' (' . $typestring . ')'
        );
    }


    // ----------------------------------------------------------------------------------------------
    // HEALTH REPORT
    // ----------------------------------------------------------------------------------------------

    // $report is HealthReport::build()'s output.
    public static function report($report) {
        $t = $report['totals'];

        if ($t['total'] == 0) {
            $key = "unknown"; $icon = "&#128202;"; $heading = __('No monitors configured');
        } elseif ($t['error'] > 0) {
            $key = "error"; $icon = "&#128680;"; $heading = __('DEGRADED — Action Required');
        } elseif ($t['warning'] > 0) {
            $key = "warning"; $icon = "&#9888;&#65039;"; $heading = __('WARNING — Attention Needed');
        } elseif ($t['unknown'] > 0) {
            $key = "unknown"; $icon = "&#10067;"; $heading = __('Some monitors have no data');
        } else {
            $key = "ok"; $icon = "&#9989;"; $heading = __('All Systems Operational');
        }
        $c = self::COLORS[$key];
        $pct = $t['total'] ? (int) round($t['ok'] * 100 / $t['total']) : 0;

        // progress bar under the heading
        $bar = '<div style="font-size:12px;opacity:.85;margin-top:20px;">'
            . self::h(sprintf(__('System health: %d%% (%d of %d monitors healthy)'), $pct, $t['ok'], $t['total'])
                . ($t['unknown'] ? ' · ' . sprintf(__('%d with no data'), $t['unknown']) : '')) . '</div>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:8px;"><tr>'
            . ($pct > 0 ? '<td width="' . $pct . '%" height="8" bgcolor="#fca5a5" style="background:rgba(255,255,255,.75);border-radius:4px 0 0 4px;font-size:0;line-height:0;' . ($pct == 100 ? 'border-radius:4px;' : '') . '">&nbsp;</td>' : '')
            . ($pct < 100 ? '<td height="8" style="background:rgba(0,0,0,.25);border-radius:' . ($pct > 0 ? '0 4px 4px 0' : '4px') . ';font-size:0;line-height:0;">&nbsp;</td>' : '')
            . '</tr></table>';

        // KPI tiles
        $tiles = [
            [$t['ok'], __('Healthy'), "#16a34a"],
            [$t['error'], __('Errors'), "#dc2626"],
            [$t['warning'], __('Warnings'), "#d97706"],
            [$t['total'], __('Monitors'), "#1e293b"],
        ];
        $kpi = '<tr><td style="padding:0;border-bottom:1px solid #e2e8f0;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>';
        foreach ($tiles as $i => $tile) {
            $kpi .= '<td width="25%" align="center" style="padding:18px 6px;' . ($i ? 'border-left:1px solid #e2e8f0;' : '') . self::FONT . '">'
                . '<div style="font-size:30px;font-weight:700;color:' . $tile[2] . ';line-height:1.1;">' . (int) $tile[0] . '</div>'
                . '<div style="font-size:11px;letter-spacing:.8px;text-transform:uppercase;color:#64748b;margin-top:4px;">' . self::h($tile[1]) . '</div>'
                . '</td>';
        }
        $kpi .= '</tr></table></td></tr>';

        // issues requiring attention
        $issues = '';
        if (!empty($report['issues'])) {
            $list = '';
            foreach ($report['issues'] as $i => $issue) {
                $ik = self::statusKey($issue['status']);
                $ic = self::COLORS[$ik];
                $list .= '<tr><td style="padding:14px 16px;background:' . $ic['soft'] . ';' . ($i ? 'border-top:1px solid #ffffff;' : '') . self::FONT . '">'
                    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
                    . '<td width="22" valign="top" style="padding-top:4px;">' . self::dot($ik) . '</td>'
                    . '<td valign="top" style="font-size:14px;color:#0f172a;">'
                        . '<strong>' . self::h($issue['name']) . '</strong> ' . self::pill($ik, $ik === "error" ? __('Error') : __('Warning'))
                        . '<div style="font-size:13px;color:#475569;margin-top:3px;">' . self::h($issue['detail']) . '</div>'
                        . '<div style="font-size:12px;color:#94a3b8;margin-top:3px;">' . self::h(__('Open since')) . ' ' . self::h($issue['since']) . '</div>'
                    . '</td>'
                    . '<td valign="top" align="right" style="font-size:12px;color:#94a3b8;white-space:nowrap;padding-left:10px;">' . self::h($issue['category']) . '</td>'
                    . '</tr></table></td></tr>';
            }
            $issues = '<tr><td style="padding:24px 28px 8px;">'
                . self::sectionTitle("&#128269;", sprintf(__('Issues requiring attention (%d)'), count($report['issues'])))
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border-radius:6px;overflow:hidden;border:1px solid #fecaca;">' . $list . '</table>'
                . '</td></tr>';
        }

        // full report by category
        $cats = '';
        foreach ($report['categories'] as $cat) {
            if (empty($cat['items'])) continue;
            $statuses = array_map('intval', array_column($cat['items'], 'status'));
            $healthy = count(array_keys($statuses, 1));
            if (in_array(3, $statuses, true)) $catKey = "error";
            elseif (in_array(2, $statuses, true)) $catKey = "warning";
            elseif ($healthy < count($statuses)) $catKey = "unknown";
            else $catKey = "ok";

            $rows = '';
            foreach ($cat['items'] as $item) {
                $ik = self::statusKey($item['status']);
                $chips = '';
                foreach ($item['chips'] as $chip) $chips .= self::chip($chip);
                $rows .= '<tr><td style="padding:12px 16px;border-top:1px solid #eef2f7;' . self::FONT . '">'
                    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
                    . '<td width="22" valign="top" style="padding-top:5px;">' . self::dot($ik) . '</td>'
                    . '<td valign="top">'
                        . '<div style="font-size:14px;font-weight:600;color:#0f172a;">' . self::h($item['name']) . '</div>'
                        . '<div style="font-size:13px;color:#475569;margin-top:2px;">' . self::h($item['detail']) . '</div>'
                        . ($chips !== '' ? '<div style="margin-top:2px;">' . $chips . '</div>' : '')
                    . '</td></tr></table></td></tr>';
            }

            $cats .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e2e8f0;border-radius:6px;border-collapse:separate;margin-bottom:16px;">'
                . '<tr><td style="padding:12px 16px;background:#f8fafc;' . self::FONT . '">'
                    . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
                    . '<td style="font-size:13px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#1e293b;">' . self::dot($catKey) . '&nbsp; ' . self::h($cat['label']) . '</td>'
                    . '<td align="right" style="font-size:12px;color:#64748b;white-space:nowrap;">' . self::pill($catKey, [ "ok" => __('OK'), "warning" => __('Warning'), "error" => __('Issues'), "unknown" => __('No data') ][$catKey]) . '&nbsp; ' . self::h(sprintf(__('%d/%d healthy'), $healthy, count($cat['items']))) . '</td>'
                    . '</tr></table>'
                . '</td></tr>'
                . $rows
                . '</table>';
        }
        if ($cats === '') {
            $cats = '<p style="' . self::FONT . 'font-size:14px;color:#64748b;margin:0;">' . self::h(__('No servers, websites or checks are being monitored yet.')) . '</p>';
        }

        $body = $kpi . $issues
            . '<tr><td style="padding:24px 28px 12px;">' . self::sectionTitle("&#128203;", __('Full report by category')) . $cats . '</td></tr>'
            . '<tr><td style="padding:0 28px 28px;">' . self::button(self::appLink("?route=dashboard"), __('Open dashboard'), $c['accent']) . '</td></tr>';

        return self::layout(
            $c['accent'],
            self::company() . ' · ' . __('Automated Health Report'),
            $icon . ' ' . self::h($heading),
            sprintf(__('Generated %s'), dateTimeDisplay(date('Y-m-d H:i:s'))) . ($report['schedule'] !== '' ? ' · ' . __('Schedule:') . ' ' . $report['schedule'] : ''),
            $bar,
            $body,
            sprintf(__('%d healthy, %d errors, %d warnings across %d monitors'), $t['ok'], $t['error'], $t['warning'], $t['total'])
        );
    }

}
