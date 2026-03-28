<?php
require_once __DIR__ . '/includes/db.php';

$db = get_db();

$db->exec("DROP TABLE IF EXISTS appointments");
$db->exec("DROP TABLE IF EXISTS services");
$db->exec("DROP TABLE IF EXISTS admin_users");

$db->exec("CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(255) UNIQUE NOT NULL,
    title_pt VARCHAR(255) NOT NULL,
    title_fr VARCHAR(255) NOT NULL,
    description_pt TEXT NOT NULL,
    description_fr TEXT NOT NULL,
    image_url VARCHAR(500) DEFAULT '',
    active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$db->exec("CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    nif VARCHAR(20) NOT NULL,
    address VARCHAR(500) NOT NULL,
    service_id INT,
    preferred_date VARCHAR(20) NOT NULL,
    preferred_time VARCHAR(10) NOT NULL,
    notes TEXT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    payment_status VARCHAR(20) DEFAULT 'unpaid',
    payment_reference VARCHAR(50) DEFAULT '',
    consultation_notes TEXT DEFAULT NULL,
    confirmed_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$db->exec("CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$services = [
    [
        'slug'           => 'procuracoes',
        'title_pt'       => 'Procurações e Representação',
        'title_fr'       => 'Procurations et Représentation',
        'description_pt' => 'O solicitador actua como procurador em nome dos seus clientes, representando-os junto de entidades públicas e privadas em Portugal. Este serviço é especialmente útil para emigrantes e cidadãos portugueses residentes em França que não podem estar presentes pessoalmente para tratar de assuntos legais e administrativos. A procuração pode ser geral ou especial, conforme as necessidades do cliente. Tratamos de todo o processo de autenticação e legalização, garantindo que os documentos têm plena validade jurídica em Portugal e no estrangeiro. Contacte-nos para saber mais sobre como podemos representá-lo.',
        'description_fr' => "Le solicitor agit en tant que mandataire au nom de ses clients, les représentant auprès des entités publiques et privées au Portugal. Ce service est particulièrement utile pour les émigrants et les citoyens portugais résidant en France qui ne peuvent pas être présents personnellement pour gérer des affaires juridiques et administratives. La procuration peut être générale ou spéciale, selon les besoins du client. Nous gérons l'ensemble du processus d'authentification et de légalisation, garantissant que les documents ont pleine validité juridique au Portugal et à l'étranger. Contactez-nous pour en savoir plus.",
        'sort_order'     => 1,
    ],
    [
        'slug'           => 'testamentos',
        'title_pt'       => 'Testamentos e Heranças',
        'title_fr'       => 'Testaments et Successions',
        'description_pt' => "Garanta que a sua vontade é respeitada e que o seu património é transmitido de acordo com os seus desejos. Oferecemos serviços completos de elaboração de testamentos, aconselhamento sobre heranças e partilhas, e representação em processos de inventário. Para emigrantes portugueses em França, ajudamos a navegar pelas complexidades do direito sucessório luso-francês, assegurando que os seus bens em Portugal são devidamente tratados. Tratamos ainda do registo de testamentos e de toda a documentação necessária junto das autoridades competentes, proporcionando tranquilidade para si e para os seus familiares.",
        'description_fr' => "Assurez-vous que votre volonté est respectée et que votre patrimoine est transmis selon vos souhaits. Nous offrons des services complets de rédaction de testaments, de conseil en matière de successions et de partages, et de représentation dans les procédures d'inventaire. Pour les émigrés portugais en France, nous aidons à naviguer dans les complexités du droit successoral franco-portugais, en veillant à ce que vos biens au Portugal soient correctement gérés. Nous nous occupons également de l'enregistrement des testaments et de toute la documentation nécessaire auprès des autorités compétentes.",
        'sort_order'     => 2,
    ],
    [
        'slug'           => 'imoveis',
        'title_pt'       => 'Compra e Venda de Imóveis',
        'title_fr'       => 'Achat et Vente Immobilière',
        'description_pt' => "Prestamos assistência completa em todas as fases da compra e venda de imóveis em Portugal. Desde a análise e verificação da documentação do imóvel, passando pela elaboração e revisão de contratos de promessa de compra e venda, até à escritura final. Para compradores e vendedores residentes no estrangeiro, oferecemos um serviço personalizado que inclui a representação por procuração, eliminando a necessidade de deslocação a Portugal. Verificamos toda a situação legal e fiscal do imóvel, identificamos eventuais ónus ou encargos, e asseguramos que a transação decorre de forma segura e transparente para todas as partes envolvidas.",
        'description_fr' => "Nous fournissons une assistance complète à toutes les étapes de l'achat et de la vente de biens immobiliers au Portugal. Depuis l'analyse et la vérification de la documentation du bien, en passant par la rédaction et la révision des contrats de promesse de vente, jusqu'à l'acte final. Pour les acheteurs et vendeurs résidant à l'étranger, nous offrons un service personnalisé incluant la représentation par procuration, éliminant la nécessité de se déplacer au Portugal. Nous vérifions toute la situation juridique et fiscale du bien, identifions les éventuelles charges ou hypothèques, et veillons à ce que la transaction se déroule de manière sûre et transparente.",
        'sort_order'     => 3,
    ],
    [
        'slug'           => 'divorcio',
        'title_pt'       => 'Divórcio e Partilhas',
        'title_fr'       => 'Divorce et Partage de Biens',
        'description_pt' => "O processo de divórcio pode ser emocionalmente difícil, mas com o apoio jurídico adequado torna-se mais simples e menos stressante. Oferecemos serviços de divórcio por mútuo consentimento e litigioso, tratando de toda a documentação e representação necessárias. Gerimos igualmente os processos de partilha de bens, regulação do poder paternal e pensões de alimentos. Para casais com bens ou residência em Portugal e em França, ajudamos a coordenar os procedimentos legais em ambos os países, garantindo que os seus direitos são plenamente protegidos durante todo o processo. Actuamos sempre com discrição, profissionalismo e respeito pela situação delicada dos nossos clientes.",
        'description_fr' => "Le processus de divorce peut être émotionnellement difficile, mais avec le bon soutien juridique, il devient plus simple et moins stressant. Nous offrons des services de divorce par consentement mutuel et contentieux, gérant toute la documentation et la représentation nécessaires. Nous gérons également les procédures de partage de biens, la régulation de l'autorité parentale et les pensions alimentaires. Pour les couples ayant des biens ou une résidence au Portugal et en France, nous aidons à coordonner les procédures légales dans les deux pays, en veillant à ce que vos droits soient pleinement protégés tout au long du processus.",
        'sort_order'     => 4,
    ],
    [
        'slug'           => 'documentos',
        'title_pt'       => 'Reconhecimento de Documentos',
        'title_fr'       => 'Reconnaissance de Documents',
        'description_pt' => "O reconhecimento de documentos é um serviço fundamental para quem necessita de validar a autenticidade de assinaturas e documentos para uso em Portugal ou no estrangeiro. Procedemos ao reconhecimento de assinaturas, autenticação de fotocópias, tradução juramentada e apostilha de documentos conforme a Convenção de Haia. Para emigrantes portugueses em França, este serviço é essencial para validar documentos académicos, profissionais ou pessoais. Também tratamos da legalização de documentos emitidos em França para uso em Portugal, assegurando que todos os procedimentos burocráticos são cumpridos de acordo com a legislação vigente em ambos os países.",
        'description_fr' => "La reconnaissance de documents est un service fondamental pour ceux qui ont besoin de valider l'authenticité des signatures et des documents pour usage au Portugal ou à l'étranger. Nous procédons à la reconnaissance de signatures, à l'authentification de photocopies, à la traduction assermentée et à l'apostille de documents conformément à la Convention de La Haye. Pour les émigrés portugais en France, ce service est essentiel pour valider des documents académiques, professionnels ou personnels. Nous nous occupons également de la légalisation des documents émis en France pour usage au Portugal.",
        'sort_order'     => 5,
    ],
    [
        'slug'           => 'registo-civil',
        'title_pt'       => 'Registo Civil e Notariado',
        'title_fr'       => 'État Civil et Notariat',
        'description_pt' => "Prestamos serviços completos de registo civil e notariado, incluindo registo de nascimentos, casamentos, óbitos e outros atos civis junto das conservatórias portuguesas. Para emigrantes, auxiliamos no registo tardio de eventos ocorridos no estrangeiro, como nascimentos e casamentos celebrados em França. Também tratamos de certidões de nascimento, casamento e óbito, bem como da atualização de documentos de identificação. No âmbito notarial, elaboramos e autenticamos declarações, contratos e outros documentos com valor jurídico. O nosso objetivo é simplificar todos estes processos burocráticos, permitindo que os nossos clientes resolvam os seus assuntos administrativos de forma eficiente e tranquila.",
        'description_fr' => "Nous fournissons des services complets d'état civil et de notariat, notamment l'enregistrement des naissances, mariages, décès et autres actes civils auprès des registres portugais. Pour les émigrés, nous aidons à l'enregistrement tardif d'événements survenus à l'étranger, tels que les naissances et mariages célébrés en France. Nous nous occupons également des actes de naissance, de mariage et de décès, ainsi que de la mise à jour des documents d'identité. Dans le domaine notarial, nous rédigeons et authentifions des déclarations, des contrats et d'autres documents ayant valeur juridique.",
        'sort_order'     => 6,
    ],
    [
        'slug'           => 'aconselhamento',
        'title_pt'       => 'Aconselhamento Jurídico',
        'title_fr'       => 'Conseil Juridique',
        'description_pt' => "O aconselhamento jurídico é a base de qualquer decisão legal bem fundamentada. Oferecemos consultas personalizadas para esclarecer dúvidas sobre direito português, ajudando os nossos clientes a compreender os seus direitos e obrigações em diversas situações. Para emigrantes e cidadãos portugueses em França, prestamos orientação sobre questões transnacionais, como conflitos de leis entre Portugal e França, direito do trabalho, previdência social e questões fiscais. As nossas consultas podem ser realizadas presencialmente ou à distância por videoconferência. Comprometemo-nos a fornecer aconselhamento claro, objetivo e acessível, independentemente da complexidade do caso apresentado.",
        'description_fr' => "Le conseil juridique est la base de toute décision juridique bien fondée. Nous offrons des consultations personnalisées pour clarifier les questions sur le droit portugais, aidant nos clients à comprendre leurs droits et obligations dans diverses situations. Pour les émigrés et les citoyens portugais en France, nous fournissons des orientations sur les questions transnationales, telles que les conflits de lois entre le Portugal et la France, le droit du travail, la sécurité sociale et les questions fiscales. Nos consultations peuvent être réalisées en personne ou à distance par vidéoconférence. Nous nous engageons à fournir des conseils clairs, objectifs et accessibles.",
        'sort_order'     => 7,
    ],
    [
        'slug'           => 'emigrantes',
        'title_pt'       => 'Serviços para Emigrantes',
        'title_fr'       => 'Services aux Émigrés',
        'description_pt' => "Especializados no apoio a emigrantes portugueses e lusodescendentes residentes em França, oferecemos um conjunto abrangente de serviços adaptados às necessidades específicas desta comunidade. Desde a manutenção de documentação portuguesa atualizada, como o cartão de cidadão e passaporte, até à gestão de bens imóveis em Portugal, passando pelo apoio em processos de regresso ao país. Ajudamos igualmente na navegação entre os sistemas legais português e francês, facilitando a vida transnacional dos nossos clientes. Compreendemos os desafios únicos enfrentados pelos emigrantes e estamos aqui para simplificar os aspectos legais e administrativos da vida entre dois países.",
        'description_fr' => "Spécialisés dans le soutien aux émigrés portugais et aux Luso-descendants résidant en France, nous offrons un ensemble complet de services adaptés aux besoins spécifiques de cette communauté. De la maintenance de la documentation portugaise à jour, comme la carte d'identité et le passeport, à la gestion de biens immobiliers au Portugal, en passant par le soutien dans les processus de retour au pays. Nous aidons également à naviguer entre les systèmes juridiques portugais et français, facilitant la vie transnationale de nos clients. Nous comprenons les défis uniques auxquels font face les émigrés et sommes là pour simplifier les aspects juridiques et administratifs de la vie entre deux pays.",
        'sort_order'     => 8,
    ],
];

$stmt = $db->prepare("INSERT INTO services (slug, title_pt, title_fr, description_pt, description_fr, image_url, active, sort_order) VALUES (?, ?, ?, ?, ?, '', 1, ?)");
foreach ($services as $s) {
    $stmt->execute([$s['slug'], $s['title_pt'], $s['title_fr'], $s['description_pt'], $s['description_fr'], $s['sort_order']]);
}

$passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
$db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")->execute(['admin', $passwordHash]);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Install</title><style>body{font-family:sans-serif;max-width:640px;margin:60px auto;padding:0 24px;background:#f9f8f6;color:#333;}h1{color:#1e3a5f;}a{color:#1e3a5f;}.warn{background:#fff3cd;border:1px solid #ffc107;padding:12px 16px;border-radius:4px;margin-top:1rem;}</style></head><body>';
echo '<h1>&#x2705; Installation Complete</h1>';
echo '<ul>';
echo '<li>MySQL database tables created</li>';
echo '<li>8 services inserted (PT + FR)</li>';
echo '<li>Admin user created: <strong>admin</strong> / <strong>admin123</strong></li>';
echo '</ul>';
echo '<p><a href="/admin/login.php">&#x1F512; Go to Admin Panel</a> &nbsp;|&nbsp; <a href="/index.php">&#x1F3E0; Go to Site</a></p>';
echo '<div class="warn"><strong>&#x26A0; Security:</strong> Delete or restrict access to <code>install.php</code> after installation.</div>';
echo '</body></html>';
