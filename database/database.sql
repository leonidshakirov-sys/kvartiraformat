SET NAMES utf8mb4;
SET time_zone = '+03:00';

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value TEXT NULL,
  updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'owner',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  success TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_login_attempts (username, ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  meta_title VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  h1 VARCHAR(255) NULL,
  body MEDIUMTEXT NULL,
  canonical_url VARCHAR(255) NULL,
  robots_index TINYINT(1) NOT NULL DEFAULT 1,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  short TEXT NULL,
  body MEDIUMTEXT NULL,
  price INT NULL,
  image_path VARCHAR(255) NULL,
  image_alt VARCHAR(255) NULL,
  meta_title VARCHAR(255) NULL,
  meta_description VARCHAR(500) NULL,
  h1 VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS geo_pages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  title VARCHAR(255) NULL,
  description VARCHAR(500) NULL,
  h1 VARCHAR(255) NULL,
  body MEDIUMTEXT NULL,
  priority INT NOT NULL DEFAULT 1,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS repair_tariffs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  repair_type VARCHAR(120) NOT NULL,
  package_type VARCHAR(160) NOT NULL,
  price_per_m2 INT NOT NULL,
  note TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_tariff_lookup (repair_type, package_type, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS advantages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  text TEXT NOT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS faqs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  answer TEXT NOT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  city VARCHAR(120) NULL,
  district VARCHAR(180) NULL,
  area VARCHAR(80) NULL,
  repair_type VARCHAR(120) NULL,
  cost VARCHAR(120) NULL,
  duration VARCHAR(120) NULL,
  works TEXT NULL,
  is_demo TINYINT(1) NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portfolio_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  portfolio_id INT UNSIGNED NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  kind ENUM('before','after','gallery') NOT NULL DEFAULT 'gallery',
  alt_text VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 100,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_portfolio_images_item FOREIGN KEY (portfolio_id) REFERENCES portfolio_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_name VARCHAR(160) NOT NULL,
  text TEXT NOT NULL,
  source_url VARCHAR(500) NULL,
  photo_path VARCHAR(255) NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255) NULL,
  mime_type VARCHAR(120) NULL,
  width INT NULL,
  height INT NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(160) NOT NULL,
  phone VARCHAR(60) NOT NULL,
  city VARCHAR(160) NULL,
  repair_type VARCHAR(120) NULL,
  property_type VARCHAR(120) NULL,
  area DECIMAL(8,2) NULL,
  rooms VARCHAR(40) NULL,
  package_type VARCHAR(160) NULL,
  extras TEXT NULL,
  comment TEXT NULL,
  estimate_text VARCHAR(255) NULL,
  page_url VARCHAR(500) NULL,
  source VARCHAR(120) NULL,
  utm_source VARCHAR(120) NULL,
  utm_medium VARCHAR(120) NULL,
  utm_campaign VARCHAR(180) NULL,
  utm_content VARCHAR(180) NULL,
  utm_term VARCHAR(180) NULL,
  referrer VARCHAR(500) NULL,
  ip_address VARCHAR(64) NULL,
  user_agent VARCHAR(500) NULL,
  consent_version VARCHAR(80) NOT NULL,
  consent_text TEXT NOT NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'new',
  manager_comment TEXT NULL,
  idempotency_key VARCHAR(80) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_leads_created (created_at),
  INDEX idx_leads_phone_created (phone, created_at),
  UNIQUE KEY uniq_leads_idempotency (idempotency_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS telegram_queue (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lead_id INT UNSIGNED NULL,
  message TEXT NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts INT NOT NULL DEFAULT 0,
  last_error TEXT NULL,
  sent_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_telegram_status (status, attempts, created_at),
  CONSTRAINT fk_telegram_lead FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  entity VARCHAR(120) NULL,
  entity_id INT UNSIGNED NULL,
  payload JSON NULL,
  ip_address VARCHAR(64) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (setting_key, setting_value, updated_at) VALUES
('brand_name','КвартираФормат',NOW()),('brand_name_full','КВАРТИРАФОРМАТ',NOW()),('brand_tagline','Ремонт квартир под ключ',NOW()),('service_area','Москва и Московская область',NOW()),('legal_name','ИП Шакиров Леонид Альбертович',NOW()),('inn','772607203680',NOW()),('phone','+7 (916) 265-92-62',NOW()),('phone_href','tel:+79162659262',NOW()),('office_address','г. Москва, 32-й км МКАД, владение 15',NOW()),('domain','kvartiraformat.ru',NOW()),('privacy_version','2026-10-10',NOW()),('route_url','https://yandex.ru/maps/?text=г.%20Москва,%2032-й%20км%20МКАД,%20владение%2015',NOW())
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW();

INSERT INTO services (title, slug, short, body, price, image_alt, meta_title, meta_description, h1, is_published, sort_order, created_at, updated_at) VALUES
('Ремонт квартир под ключ','remont-kvartir-pod-klyuch','Комплексный ремонт квартиры от подготовки основания до финальной отделки.','<p>Комплексный ремонт под ключ помогает объединить черновые, инженерные и финишные работы в один управляемый процесс. На странице можно добавить состав работ, условия замера и локальные особенности.</p>',NULL,'Современный интерьер квартиры','Ремонт квартир под ключ в Москве — расчёт стоимости | КвартираФормат','Комплексный ремонт квартир под ключ в Москве и Московской области. Расчёт стоимости, заявка на замер, управление сметой.','Ремонт квартир под ключ в Москве',1,10,NOW(),NOW()),
('Капитальный ремонт','kapitalnyy-remont','Глубокое обновление квартиры с инженерными работами и заменой отделки.','<p>Капитальный ремонт подходит для квартир, где нужно обновить инженерные системы, основания и финишную отделку. Итоговый состав работ определяется после осмотра.</p>',NULL,'Капитальный ремонт квартиры','Капитальный ремонт квартир в Москве | КвартираФормат','Капитальный ремонт квартир: демонтаж, черновые работы, инженерия и отделка.','Капитальный ремонт квартир',1,20,NOW(),NOW()),
('Косметический ремонт','kosmeticheskiy-remont','Обновление финишных покрытий без полной перепланировки.','<p>Косметический ремонт позволяет обновить внешний вид квартиры без полного переустройства. Точный перечень зависит от состояния помещений.</p>',NULL,'Косметический ремонт квартиры','Косметический ремонт квартиры в Москве | КвартираФормат','Косметический ремонт квартир с расчётом стоимости по параметрам объекта.','Косметический ремонт квартиры',1,30,NOW(),NOW()),
('Дизайнерский ремонт','dizaynerskiy-remont','Ремонт с проработанными решениями по стилю, свету и материалам.','<p>Дизайнерский ремонт требует согласования решений по планировке, свету, материалам и деталям. Страница наполняется примерами и составом работ.</p>',NULL,'Дизайнерский ремонт квартиры','Дизайнерский ремонт квартиры в Москве | КвартираФормат','Дизайнерский ремонт квартир в Москве и области: заявка на расчёт и замер.','Дизайнерский ремонт квартиры',1,40,NOW(),NOW()),
('Ремонт квартир в новостройках','remont-novostroek','Отделка квартир без отделки или с предчистовой подготовкой.','<p>Новостройки часто требуют комплексной черновой подготовки, разводки инженерных систем и финишной отделки.</p>',NULL,'Ремонт новостройки','Ремонт квартир в новостройке | КвартираФормат','Ремонт квартир в новостройках в Москве и Подмосковье.','Ремонт квартир в новостройках',1,50,NOW(),NOW()),
('Ремонт вторичного жилья','remont-vtorichnogo-zhilya','Обновление квартир во вторичном фонде с демонтажом старой отделки.','<p>Во вторичном жилье важны обследование старой отделки, демонтаж и проверка инженерных систем.</p>',NULL,'Ремонт вторичного жилья','Ремонт вторички под ключ | КвартираФормат','Ремонт квартир во вторичном жилом фонде: расчёт, замер, смета.','Ремонт вторичного жилья',1,60,NOW(),NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title), short=VALUES(short), updated_at=NOW();

INSERT INTO geo_pages (name, slug, title, description, h1, body, priority, is_published, created_at, updated_at) VALUES
('Москва','moskva','Ремонт квартир под ключ в Москве — расчёт стоимости | КвартираФормат','Ремонт квартир под ключ в Москве: услуги, расчёт стоимости и заявка на замер.','Ремонт квартир под ключ в Москве','<p>Для Москвы важно учитывать тип дома, правила проведения шумных работ и логистику материалов. Добавьте реальные примеры объектов после их появления.</p>',1,1,NOW(),NOW()),
('Домодедово','domodedovo','Ремонт квартир под ключ в Домодедово — расчёт стоимости | КвартираФормат','Ремонт квартир под ключ в Домодедово: расчёт стоимости, виды ремонта и заявка.','Ремонт квартир под ключ в Домодедово','<p>В Домодедово востребованы ремонты квартир в новостройках и вторичном жилье. Страница готова для добавления локальных кейсов.</p>',1,1,NOW(),NOW()),
('Подольск','podolsk','Ремонт квартир под ключ в Подольске — расчёт стоимости | КвартираФормат','Ремонт квартир в Подольске с расчётом стоимости и заявкой на замер.','Ремонт квартир под ключ в Подольске','<p>Подольск входит в приоритетную географию сайта. Добавляйте полезную локальную информацию и реальные объекты.</p>',1,1,NOW(),NOW()),
('Видное','vidnoe','Ремонт квартир под ключ в Видном — расчёт стоимости | КвартираФормат','Ремонт квартир в Видном: услуги, калькулятор и заявка.','Ремонт квартир под ключ в Видном','<p>Страница предназначена для аккуратного SEO-продвижения без массовой генерации пустых посадочных.</p>',1,1,NOW(),NOW()),
('Щербинка','shcherbinka','Ремонт квартир под ключ в Щербинке — расчёт стоимости | КвартираФормат','Ремонт квартир в Щербинке: комплексный подход и заявка на расчёт.','Ремонт квартир под ключ в Щербинке','<p>Опишите особенности ремонта квартир в Щербинке и добавьте реальные кейсы после выполнения работ.</p>',1,1,NOW(),NOW()),
('Южное Бутово','yuzhnoe-butovo','Ремонт квартир под ключ в Южном Бутово — расчёт стоимости | КвартираФормат','Ремонт квартир в Южном Бутово: калькулятор и заявка на замер.','Ремонт квартир под ключ в Южном Бутово','<p>Южное Бутово включено в первую очередь географии. Текст можно расширить через админ-панель.</p>',1,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), updated_at=NOW();

INSERT INTO advantages (title, text, is_published, sort_order, created_at, updated_at) VALUES
('Подробная смета','Фиксируем состав работ и материалов в понятном документе до старта ремонта.',1,10,NOW(),NOW()),('Организация ремонта под ключ','Помогаем пройти путь от черновой отделки до готового интерьера.',1,20,NOW(),NOW()),('Поэтапный контроль качества','Работы принимаются этапами, чтобы замечания не копились до финала.',1,30,NOW(),NOW()),('Работа по договору','Существенные условия и объём взаимодействия фиксируются документально.',1,40,NOW(),NOW()),('Помощь с материалами','Подсказываем по подбору, закупке и доставке черновых материалов.',1,50,NOW(),NOW()),('Фотоотчёты','Можно настроить регулярную отправку фотографий выполненных этапов.',1,60,NOW(),NOW()),('Согласование сроков','План работ формируется с учётом особенностей квартиры и материалов.',1,70,NOW(),NOW()),('Гарантийные обязательства','Условия гарантии указываются в договоре и не подменяются рекламными обещаниями.',1,80,NOW(),NOW());

INSERT INTO faqs (question, answer, is_published, sort_order, created_at, updated_at) VALUES
('Можно ли узнать стоимость ремонта онлайн?','Да. Калькулятор собирает исходные данные и показывает предварительный расчёт, если тарифы внесены в админ-панель.',1,10,NOW(),NOW()),('Почему часть цен указана как «По расчёту»?','Мы не публикуем неподтверждённые цены. Владелец может внести тарифы в админ-панели.',1,20,NOW(),NOW()),('Работаете ли вы с новостройками?','Да, предусмотрены сценарии ремонта квартир без отделки и с предчистовой подготовкой.',1,30,NOW(),NOW()),('Можно ли заказать капитальный ремонт вторичного жилья?','Да. Для вторичного жилья учитываются демонтаж, состояние инженерных систем и ограничения дома.',1,40,NOW(),NOW()),('Можно ли делать ремонт с материалами?','Калькулятор поддерживает варианты только работ, работ с черновыми материалами и комплексного ремонта.',1,50,NOW(),NOW()),('Нужен ли выезд на замер?','Для точной сметы замер и осмотр объекта обычно необходимы.',1,60,NOW(),NOW()),('Как обрабатываются персональные данные?','Заявки сохраняются в базе данных, факт согласия фиксируется. Документы доступны на отдельных страницах.',1,70,NOW(),NOW()),('Можно ли изменить телефон и реквизиты на сайте?','Да. Реквизиты редактируются в настройках компании административной панели.',1,80,NOW(),NOW()),('Как отправляются заявки в Telegram?','После сохранения в MySQL заявка ставится в очередь Telegram; повторная отправка выполняется через Cron.',1,90,NOW(),NOW()),('Можно ли создать страницу под конкретный город?','Да. Географические страницы управляются через админ-панель, массовая генерация пустых страниц не используется.',1,100,NOW(),NOW());

INSERT INTO portfolio_items (title, city, district, area, repair_type, cost, duration, works, is_demo, is_published, sort_order, created_at, updated_at) VALUES
('Демонстрационная карточка объекта','Москва','пример заполнения','62 м²','Капитальный ремонт','По расчёту','Срок задаётся в админ-панели','Демонстрационные материалы показывают формат карточки. Замените их реальными объектами перед публикацией.',1,1,10,NOW(),NOW());
