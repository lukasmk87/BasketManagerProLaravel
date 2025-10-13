<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'privacy',
                'title' => 'Datenschutzerklärung',
                'meta_description' => 'Informationen zum Datenschutz und zur Verarbeitung personenbezogener Daten bei BasketManager Pro.',
                'content' => $this->getPrivacyContent(),
                'is_published' => true,
            ],
            [
                'slug' => 'terms',
                'title' => 'Allgemeine Geschäftsbedingungen (AGB)',
                'meta_description' => 'Die Allgemeinen Geschäftsbedingungen für die Nutzung von BasketManager Pro.',
                'content' => $this->getTermsContent(),
                'is_published' => true,
            ],
            [
                'slug' => 'imprint',
                'title' => 'Impressum',
                'meta_description' => 'Rechtliche Angaben und Kontaktinformationen von BasketManager Pro.',
                'content' => $this->getImprintContent(),
                'is_published' => true,
            ],
            [
                'slug' => 'gdpr',
                'title' => 'GDPR / DSGVO Informationen',
                'meta_description' => 'Informationen zur DSGVO-Konformität und Ihren Rechten als Nutzer von BasketManager Pro.',
                'content' => $this->getGdprContent(),
                'is_published' => true,
            ],
        ];

        foreach ($pages as $page) {
            LegalPage::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }

    /**
     * Get privacy policy content.
     */
    private function getPrivacyContent(): string
    {
        return <<<'HTML'
<h2>1. Datenschutz auf einen Blick</h2>

<h3>Allgemeine Hinweise</h3>
<p>Die folgenden Hinweise geben einen einfachen Überblick darüber, was mit Ihren personenbezogenen Daten passiert, wenn Sie diese Website besuchen. Personenbezogene Daten sind alle Daten, mit denen Sie persönlich identifiziert werden können.</p>

<h3>Datenerfassung auf dieser Website</h3>
<h4>Wer ist verantwortlich für die Datenerfassung auf dieser Website?</h4>
<p>Die Datenverarbeitung auf dieser Website erfolgt durch den Websitebetreiber. Dessen Kontaktdaten können Sie dem Impressum dieser Website entnehmen.</p>

<h4>Wie erfassen wir Ihre Daten?</h4>
<p>Ihre Daten werden zum einen dadurch erhoben, dass Sie uns diese mitteilen. Hierbei kann es sich z.B. um Daten handeln, die Sie in ein Kontaktformular eingeben.</p>
<p>Andere Daten werden automatisch beim Besuch der Website durch unsere IT-Systeme erfasst. Das sind vor allem technische Daten (z.B. Internetbrowser, Betriebssystem oder Uhrzeit des Seitenaufrufs).</p>

<h4>Wofür nutzen wir Ihre Daten?</h4>
<p>Ein Teil der Daten wird erhoben, um eine fehlerfreie Bereitstellung der Website zu gewährleisten. Andere Daten können zur Analyse Ihres Nutzerverhaltens verwendet werden.</p>

<h4>Welche Rechte haben Sie bezüglich Ihrer Daten?</h4>
<p>Sie haben jederzeit das Recht unentgeltlich Auskunft über Herkunft, Empfänger und Zweck Ihrer gespeicherten personenbezogenen Daten zu erhalten. Sie haben außerdem ein Recht, die Berichtigung oder Löschung dieser Daten zu verlangen.</p>

<h2>2. Hosting und Content Delivery Networks (CDN)</h2>

<h3>Externes Hosting</h3>
<p>Diese Website wird bei einem externen Dienstleister gehostet (Hoster). Die personenbezogenen Daten, die auf dieser Website erfasst werden, werden auf den Servern des Hosters gespeichert. Hierbei kann es sich v.a. um IP-Adressen, Kontaktanfragen, Meta- und Kommunikationsdaten, Vertragsdaten, Kontaktdaten, Namen, Webseitenzugriffe und sonstige Daten, die über eine Website generiert werden, handeln.</p>
<p>Der Einsatz des Hosters erfolgt zum Zwecke der Vertragserfüllung gegenüber unseren potenziellen und bestehenden Kunden (Art. 6 Abs. 1 lit. b DSGVO) und im Interesse einer sicheren, schnellen und effizienten Bereitstellung unseres Online-Angebots durch einen professionellen Anbieter (Art. 6 Abs. 1 lit. f DSGVO).</p>

<h2>3. Allgemeine Hinweise und Pflichtinformationen</h2>

<h3>Datenschutz</h3>
<p>Die Betreiber dieser Seiten nehmen den Schutz Ihrer persönlichen Daten sehr ernst. Wir behandeln Ihre personenbezogenen Daten vertraulich und entsprechend der gesetzlichen Datenschutzvorschriften sowie dieser Datenschutzerklärung.</p>

<h3>Hinweis zur verantwortlichen Stelle</h3>
<p>Die verantwortliche Stelle für die Datenverarbeitung auf dieser Website ist:</p>
<p>
BasketManager Pro<br>
[Ihre Adresse]<br>
[PLZ und Ort]<br>
Deutschland
</p>
<p>Telefon: [Ihre Telefonnummer]<br>
E-Mail: datenschutz@basketmanager-pro.de</p>

<h3>Widerruf Ihrer Einwilligung zur Datenverarbeitung</h3>
<p>Viele Datenverarbeitungsvorgänge sind nur mit Ihrer ausdrücklichen Einwilligung möglich. Sie können eine bereits erteilte Einwilligung jederzeit widerrufen. Die Rechtmäßigkeit der bis zum Widerruf erfolgten Datenverarbeitung bleibt vom Widerruf unberührt.</p>

<h3>SSL- bzw. TLS-Verschlüsselung</h3>
<p>Diese Seite nutzt aus Sicherheitsgründen und zum Schutz der Übertragung vertraulicher Inhalte eine SSL- bzw. TLS-Verschlüsselung. Eine verschlüsselte Verbindung erkennen Sie daran, dass die Adresszeile des Browsers von "http://" auf "https://" wechselt und an dem Schloss-Symbol in Ihrer Browserzeile.</p>

<h2>4. Datenerfassung auf dieser Website</h2>

<h3>Cookies</h3>
<p>Unsere Internetseiten verwenden so genannte "Cookies". Cookies sind kleine Textdateien und richten auf Ihrem Endgerät keinen Schaden an. Sie werden entweder vorübergehend für die Dauer einer Sitzung (Session-Cookies) oder dauerhaft (permanente Cookies) auf Ihrem Endgerät gespeichert.</p>
<p>Session-Cookies werden nach Ende Ihres Besuchs automatisch gelöscht. Permanente Cookies bleiben auf Ihrem Endgerät gespeichert, bis Sie diese selbst löschen oder eine automatische Löschung durch Ihren Webbrowser erfolgt.</p>

<h3>Server-Log-Dateien</h3>
<p>Der Provider der Seiten erhebt und speichert automatisch Informationen in so genannten Server-Log-Dateien, die Ihr Browser automatisch an uns übermittelt. Dies sind:</p>
<ul>
  <li>Browsertyp und Browserversion</li>
  <li>verwendetes Betriebssystem</li>
  <li>Referrer URL</li>
  <li>Hostname des zugreifenden Rechners</li>
  <li>Uhrzeit der Serveranfrage</li>
  <li>IP-Adresse</li>
</ul>
<p>Eine Zusammenführung dieser Daten mit anderen Datenquellen wird nicht vorgenommen.</p>

<h3>Kontaktformular</h3>
<p>Wenn Sie uns per Kontaktformular Anfragen zukommen lassen, werden Ihre Angaben aus dem Anfrageformular inklusive der von Ihnen dort angegebenen Kontaktdaten zwecks Bearbeitung der Anfrage und für den Fall von Anschlussfragen bei uns gespeichert.</p>

<h3>Registrierung auf dieser Website</h3>
<p>Sie können sich auf dieser Website registrieren, um zusätzliche Funktionen zu nutzen. Die dazu eingegebenen Daten verwenden wir nur zum Zwecke der Nutzung des jeweiligen Angebotes oder Dienstes, für den Sie sich registriert haben.</p>

<h2>5. Plugins und Tools</h2>

<h3>Google Web Fonts (lokales Hosting)</h3>
<p>Diese Seite nutzt zur einheitlichen Darstellung von Schriftarten so genannte Web Fonts, die lokal auf unserem Server gehostet werden. Eine Verbindung zu Servern von Google findet dabei nicht statt.</p>

<p><strong>Stand:</strong> Januar 2025</p>
HTML;
    }

    /**
     * Get terms and conditions content.
     */
    private function getTermsContent(): string
    {
        return <<<'HTML'
<h2>1. Geltungsbereich</h2>
<p>Diese Allgemeinen Geschäftsbedingungen (im Folgenden "AGB") gelten für alle Verträge zwischen BasketManager Pro (im Folgenden "Anbieter") und den Nutzern (im Folgenden "Kunde") über die Nutzung der SaaS-Plattform BasketManager Pro.</p>

<h2>2. Vertragsgegenstand</h2>
<p>Der Anbieter stellt dem Kunden eine cloudbasierte Softwarelösung zur Basketball-Vereinsverwaltung zur Verfügung. Die genauen Leistungen ergeben sich aus der Leistungsbeschreibung auf der Website und dem gewählten Tarif.</p>

<h3>2.1 Leistungsumfang</h3>
<ul>
  <li>Team- und Spielerverwaltung</li>
  <li>Live-Scoring und Statistiken</li>
  <li>Training-Management</li>
  <li>Turnierverwaltung</li>
  <li>Mobile Progressive Web App (PWA)</li>
  <li>GDPR-konforme Datenspeicherung</li>
</ul>

<h3>2.2 Verfügbarkeit</h3>
<p>Der Anbieter strebt eine Verfügbarkeit der Plattform von 99,9% im Jahresmittel an. Ausgenommen hiervon sind Wartungsarbeiten, die in der Regel zu verkehrsarmen Zeiten durchgeführt werden.</p>

<h2>3. Vertragsschluss und Laufzeit</h2>

<h3>3.1 Vertragsschluss</h3>
<p>Der Vertrag kommt durch Registrierung des Kunden und Auswahl eines Tarifes zustande. Mit der Registrierung gibt der Kunde ein verbindliches Angebot zum Abschluss eines Nutzungsvertrages ab.</p>

<h3>3.2 Testphase</h3>
<p>Neukunden erhalten eine kostenlose Testphase von 30 Tagen. Während der Testphase kann der Kunde alle Features des gewählten Tarifs uneingeschränkt nutzen.</p>

<h3>3.3 Laufzeit und Kündigung</h3>
<p>Die Vertragslaufzeit beträgt einen Monat und verlängert sich automatisch um einen weiteren Monat, sofern nicht mit einer Frist von 7 Tagen zum Monatsende gekündigt wird. Die Kündigung kann jederzeit über das Kundenkonto erfolgen.</p>

<h2>4. Preise und Zahlungsbedingungen</h2>

<h3>4.1 Preise</h3>
<p>Es gelten die auf der Website angegebenen Preise zum Zeitpunkt der Bestellung. Alle Preise verstehen sich inklusive der gesetzlichen Mehrwertsteuer.</p>

<h3>4.2 Zahlungsweise</h3>
<p>Die Bezahlung erfolgt monatlich im Voraus per SEPA-Lastschrift, Kreditkarte oder PayPal. Die erste Zahlung ist unmittelbar nach Ablauf der Testphase fällig.</p>

<h3>4.3 Zahlungsverzug</h3>
<p>Bei Zahlungsverzug ist der Anbieter berechtigt, den Zugang zur Plattform zu sperren. Der Kunde bleibt zur Zahlung verpflichtet.</p>

<h2>5. Pflichten des Kunden</h2>

<h3>5.1 Zugangsdaten</h3>
<p>Der Kunde ist verpflichtet, seine Zugangsdaten geheim zu halten und vor dem Zugriff Dritter zu schützen. Bei Verlust oder Kompromittierung der Zugangsdaten ist der Anbieter unverzüglich zu informieren.</p>

<h3>5.2 Unzulässige Nutzung</h3>
<p>Der Kunde verpflichtet sich, die Plattform nicht missbräuchlich zu nutzen. Insbesondere ist untersagt:</p>
<ul>
  <li>Die Verbreitung rechtswidriger Inhalte</li>
  <li>Spam oder unerwünschte Massenmails</li>
  <li>Versuche, in das System einzudringen oder es zu manipulieren</li>
  <li>Die kommerzielle Weitergabe der Zugangsdaten</li>
</ul>

<h2>6. Datenschutz und Datensicherheit</h2>

<h3>6.1 GDPR-Konformität</h3>
<p>Der Anbieter verarbeitet alle Daten gemäß der EU-Datenschutz-Grundverordnung (DSGVO). Näheres regelt die Datenschutzerklärung.</p>

<h3>6.2 Datensicherung</h3>
<p>Der Anbieter führt regelmäßige Backups durch. Dennoch wird dem Kunden empfohlen, wichtige Daten zusätzlich lokal zu sichern.</p>

<h3>6.3 Datenportabilität</h3>
<p>Der Kunde kann seine Daten jederzeit in maschinenlesbaren Formaten (CSV, JSON) exportieren.</p>

<h2>7. Haftung</h2>

<h3>7.1 Haftungsbeschränkung</h3>
<p>Der Anbieter haftet unbeschränkt für Vorsatz und grobe Fahrlässigkeit. Für leichte Fahrlässigkeit haftet der Anbieter nur bei Verletzung wesentlicher Vertragspflichten (Kardinalpflichten).</p>

<h3>7.2 Datenverlust</h3>
<p>Der Anbieter haftet nicht für Datenverluste, soweit diese durch eine regelmäßige und vollständige Sicherung aller Daten durch den Kunden vermeidbar gewesen wären.</p>

<h2>8. Änderungen der AGB</h2>
<p>Der Anbieter behält sich das Recht vor, diese AGB mit einer Ankündigungsfrist von 6 Wochen zu ändern. Widerspricht der Kunde der Änderung nicht innerhalb von 4 Wochen nach Bekanntgabe, gelten die geänderten AGB als akzeptiert.</p>

<h2>9. Schlussbestimmungen</h2>

<h3>9.1 Gerichtsstand</h3>
<p>Sofern der Kunde Kaufmann ist, ist ausschließlicher Gerichtsstand für alle Streitigkeiten aus diesem Vertrag der Sitz des Anbieters.</p>

<h3>9.2 Anwendbares Recht</h3>
<p>Es gilt deutsches Recht unter Ausschluss des UN-Kaufrechts.</p>

<h3>9.3 Salvatorische Klausel</h3>
<p>Sollten einzelne Bestimmungen dieser AGB unwirksam sein oder werden, bleibt die Wirksamkeit der übrigen Bestimmungen hiervon unberührt.</p>

<p><strong>Stand:</strong> Januar 2025</p>
HTML;
    }

    /**
     * Get imprint content.
     */
    private function getImprintContent(): string
    {
        return <<<'HTML'
<h2>Angaben gemäß § 5 TMG</h2>

<p>
<strong>BasketManager Pro</strong><br>
[Unternehmensname / Inhabername]<br>
[Straße und Hausnummer]<br>
[PLZ und Ort]<br>
Deutschland
</p>

<h3>Kontakt</h3>
<p>
Telefon: +49 (0) XXX XXXXXXX<br>
E-Mail: info@basketmanager-pro.de<br>
Website: www.basketmanager-pro.de
</p>

<h3>Geschäftsführung</h3>
<p>[Name des Geschäftsführers / Inhabers]</p>

<h3>Registereintrag</h3>
<p>
[Falls vorhanden:]<br>
Eintragung im Handelsregister<br>
Registergericht: [Amtsgericht]<br>
Registernummer: [HRB-Nummer]
</p>

<h3>Umsatzsteuer-ID</h3>
<p>
Umsatzsteuer-Identifikationsnummer gemäß §27 a Umsatzsteuergesetz:<br>
DE [Ihre USt-IdNr.]
</p>

<h2>Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
<p>
[Name]<br>
[Adresse]
</p>

<h2>EU-Streitschlichtung</h2>
<p>Die Europäische Kommission stellt eine Plattform zur Online-Streitbeilegung (OS) bereit:
<a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener noreferrer">https://ec.europa.eu/consumers/odr/</a><br>
Unsere E-Mail-Adresse finden Sie oben im Impressum.</p>

<h2>Verbraucherstreitbeilegung/Universalschlichtungsstelle</h2>
<p>Wir sind nicht bereit oder verpflichtet, an Streitbeilegungsverfahren vor einer Verbraucherschlichtungsstelle teilzunehmen.</p>

<h2>Haftung für Inhalte</h2>
<p>Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.</p>

<p>Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden von entsprechenden Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.</p>

<h2>Haftung für Links</h2>
<p>Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.</p>

<p>Die verlinkten Seiten wurden zum Zeitpunkt der Verlinkung auf mögliche Rechtsverstöße überprüft. Rechtswidrige Inhalte waren zum Zeitpunkt der Verlinkung nicht erkennbar. Eine permanente inhaltliche Kontrolle der verlinkten Seiten ist jedoch ohne konkrete Anhaltspunkte einer Rechtsverletzung nicht zumutbar. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Links umgehend entfernen.</p>

<h2>Urheberrecht</h2>
<p>Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung außerhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers. Downloads und Kopien dieser Seite sind nur für den privaten, nicht kommerziellen Gebrauch gestattet.</p>

<p>Soweit die Inhalte auf dieser Seite nicht vom Betreiber erstellt wurden, werden die Urheberrechte Dritter beachtet. Insbesondere werden Inhalte Dritter als solche gekennzeichnet. Sollten Sie trotzdem auf eine Urheberrechtsverletzung aufmerksam werden, bitten wir um einen entsprechenden Hinweis. Bei Bekanntwerden von Rechtsverletzungen werden wir derartige Inhalte umgehend entfernen.</p>

<h2>Bildnachweise</h2>
<p>
Verwendete Icons und Grafiken:<br>
- Heroicons (MIT License)<br>
- Unsplash (freie Lizenz)<br>
- Eigene Grafiken
</p>

<p><strong>Stand:</strong> Januar 2025</p>
HTML;
    }

    /**
     * Get GDPR information content.
     */
    private function getGdprContent(): string
    {
        return <<<'HTML'
<h2>DSGVO-Konformität bei BasketManager Pro</h2>

<p>BasketManager Pro wurde von Grund auf DSGVO-konform entwickelt. Wir nehmen den Schutz Ihrer personenbezogenen Daten sehr ernst und halten uns strikt an die Vorgaben der EU-Datenschutz-Grundverordnung (DSGVO) und des Bundesdatenschutzgesetzes (BDSG).</p>

<h2>1. Ihre Rechte nach der DSGVO</h2>

<p>Als Nutzer unserer Plattform haben Sie folgende Rechte:</p>

<h3>Art. 15 DSGVO - Recht auf Auskunft</h3>
<p>Sie haben das Recht, jederzeit Auskunft über die von uns gespeicherten personenbezogenen Daten zu erhalten. Diese Auskunft können Sie direkt in Ihrem Account unter "Einstellungen → Datenschutz → Datenexport" anfordern.</p>

<h3>Art. 16 DSGVO - Recht auf Berichtigung</h3>
<p>Sie können Ihre persönlichen Daten jederzeit in Ihrem Profil bearbeiten und aktualisieren.</p>

<h3>Art. 17 DSGVO - Recht auf Löschung ("Recht auf Vergessenwerden")</h3>
<p>Sie können die vollständige Löschung Ihres Accounts und aller damit verbundenen Daten jederzeit beantragen. Dies können Sie unter "Einstellungen → Account löschen" veranlassen oder per E-Mail an datenschutz@basketmanager-pro.de.</p>

<h3>Art. 18 DSGVO - Recht auf Einschränkung der Verarbeitung</h3>
<p>Sie können die Verarbeitung Ihrer Daten einschränken lassen. Kontaktieren Sie uns hierzu unter datenschutz@basketmanager-pro.de.</p>

<h3>Art. 20 DSGVO - Recht auf Datenübertragbarkeit</h3>
<p>Sie können Ihre Daten in einem strukturierten, maschinenlesbaren Format (JSON, CSV, Excel) exportieren. Der Export ist unter "Einstellungen → Datenschutz → Datenexport" verfügbar.</p>

<h3>Art. 21 DSGVO - Widerspruchsrecht</h3>
<p>Sie haben das Recht, der Verarbeitung Ihrer personenbezogenen Daten jederzeit zu widersprechen.</p>

<h3>Art. 77 DSGVO - Beschwerderecht bei der Aufsichtsbehörde</h3>
<p>Sie haben das Recht, sich bei einer Datenschutz-Aufsichtsbehörde zu beschweren.</p>

<h2>2. Welche Daten wir speichern</h2>

<h3>Account-Daten</h3>
<ul>
  <li>Name und E-Mail-Adresse</li>
  <li>Passwort (verschlüsselt mit bcrypt)</li>
  <li>Profilbild (optional)</li>
  <li>Telefonnummer (optional)</li>
</ul>

<h3>Vereins- und Teamdaten</h3>
<ul>
  <li>Vereins- und Teaminformationen</li>
  <li>Spielerdaten (Name, Geburtsdatum, Position, Trikotnummer)</li>
  <li>Spielstatistiken</li>
  <li>Trainingspläne und Anwesenheitslisten</li>
</ul>

<h3>Notfallkontakte</h3>
<ul>
  <li>Notfallkontaktinformationen (nur mit expliziter Einwilligung)</li>
  <li>Medizinische Informationen (Allergien, Blutgruppe) - nur mit Einwilligung</li>
</ul>

<h3>Nutzungsdaten</h3>
<ul>
  <li>Login-Daten (Zeitpunkt, IP-Adresse)</li>
  <li>Nutzungsstatistiken (anonymisiert)</li>
  <li>API-Zugriffe und Quota-Tracking</li>
</ul>

<h2>3. Rechtsgrundlagen der Datenverarbeitung</h2>

<p>Wir verarbeiten Ihre Daten auf Basis folgender Rechtsgrundlagen:</p>

<ul>
  <li><strong>Art. 6 Abs. 1 lit. b DSGVO</strong> - Vertragserfüllung: Zur Bereitstellung unserer Dienstleistung</li>
  <li><strong>Art. 6 Abs. 1 lit. a DSGVO</strong> - Einwilligung: Für optionale Features wie Notfallkontakte</li>
  <li><strong>Art. 6 Abs. 1 lit. f DSGVO</strong> - Berechtigtes Interesse: Für Systemsicherheit und Betrugsbekämpfung</li>
  <li><strong>Art. 6 Abs. 1 lit. c DSGVO</strong> - Rechtliche Verpflichtung: Für steuerrechtlich relevante Daten</li>
</ul>

<h2>4. Datenspeicherung und -sicherheit</h2>

<h3>Speicherort</h3>
<p>Alle Daten werden ausschließlich auf Servern in Deutschland (Rechenzentrum in Frankfurt am Main) gespeichert. Es erfolgt keine Übertragung in Drittländer außerhalb der EU.</p>

<h3>Verschlüsselung</h3>
<ul>
  <li>Übertragung: TLS 1.3 Verschlüsselung</li>
  <li>Speicherung: AES-256 Verschlüsselung für sensitive Daten</li>
  <li>Passwörter: bcrypt-Hashing mit Salts</li>
  <li>API-Keys: SHA-256 Hashing</li>
</ul>

<h3>Backups</h3>
<p>Tägliche verschlüsselte Backups mit 30-tägiger Aufbewahrung. Backups werden nach Ablauf automatisch gelöscht.</p>

<h3>Zugriffskontrolle</h3>
<ul>
  <li>Row Level Security (RLS) auf Datenbankebene</li>
  <li>Rollenbasierte Zugriffskontrolle (RBAC) mit 11 verschiedenen Rollen</li>
  <li>2-Faktor-Authentifizierung (optional)</li>
  <li>Automatische Session-Timeout nach 2 Stunden Inaktivität</li>
</ul>

<h2>5. Auftragsverarbeiter</h2>

<p>Wir setzen folgende Auftragsverarbeiter ein, mit denen jeweils ein Auftragsverarbeitungsvertrag (AVV) nach Art. 28 DSGVO geschlossen wurde:</p>

<ul>
  <li><strong>Hosting:</strong> [Hosting-Provider] (Server in Deutschland)</li>
  <li><strong>E-Mail-Versand:</strong> [E-Mail-Service] (EU-basiert)</li>
  <li><strong>Zahlungsabwicklung:</strong> Stripe (GDPR-konform, Privacy Shield zertifiziert)</li>
  <li><strong>Monitoring:</strong> [Monitoring-Service] (EU-basiert)</li>
</ul>

<h2>6. Datenlöschung und Aufbewahrungsfristen</h2>

<h3>Automatische Löschung</h3>
<ul>
  <li>Inaktive Accounts: Löschung nach 24 Monaten Inaktivität (mit vorheriger Benachrichtigung)</li>
  <li>Log-Dateien: Löschung nach 90 Tagen</li>
  <li>Session-Daten: Löschung nach 24 Stunden</li>
</ul>

<h3>Manuelle Löschung</h3>
<p>Bei Account-Löschung werden alle personenbezogenen Daten innerhalb von 30 Tagen vollständig und unwiderruflich gelöscht. Ausnahmen gelten nur für Daten, die aufgrund gesetzlicher Aufbewahrungspflichten (z.B. Steuerrecht: 10 Jahre) länger aufbewahrt werden müssen.</p>

<h3>Aufbewahrungspflichten</h3>
<ul>
  <li>Rechnungsdaten: 10 Jahre (§ 147 AO)</li>
  <li>Vertragsdaten: 6 Jahre (§ 257 HGB)</li>
</ul>

<h2>7. Minderjährigenschutz</h2>

<p>Gemäß Art. 8 DSGVO benötigen Minderjährige unter 16 Jahren die Einwilligung ihrer Eltern oder Erziehungsberechtigten. Wir haben spezielle Funktionen implementiert:</p>

<ul>
  <li>Eltern-Account-Verknüpfung</li>
  <li>Einwilligungsmanagement für Minderjährige</li>
  <li>Eingeschränkte Datenverarbeitung für Minderjährige</li>
</ul>

<h2>8. Cookies und Tracking</h2>

<p>Wir verwenden ausschließlich technisch notwendige Cookies für:</p>
<ul>
  <li>Session-Management (Login-Session)</li>
  <li>CSRF-Schutz</li>
  <li>Spracheinstellungen</li>
</ul>

<p>Wir setzen <strong>keine</strong> Tracking-Cookies oder Analyse-Tools von Drittanbietern ein.</p>

<h2>9. Kontakt Datenschutzbeauftragter</h2>

<p>
<strong>Datenschutzbeauftragter</strong><br>
[Name des Datenschutzbeauftragten]<br>
BasketManager Pro<br>
[Adresse]<br><br>

E-Mail: datenschutz@basketmanager-pro.de<br>
Telefon: [Telefonnummer]
</p>

<h2>10. Änderungen dieser DSGVO-Informationen</h2>

<p>Wir behalten uns vor, diese DSGVO-Informationen anzupassen, um sie an geänderte Rechtslagen oder Änderungen unserer Dienstleistungen anzupassen. Die aktuelle Version ist stets auf unserer Website verfügbar.</p>

<p><strong>Stand:</strong> Januar 2025</p>
HTML;
    }
}
