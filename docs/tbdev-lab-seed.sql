-- Lab-only fixture. Do not apply to a production database automatically.
UPDATE categories SET image = 'cat_test.gif' WHERE name = 'Test Files';
UPDATE categories SET image = 'cat_software.gif' WHERE name = 'Open Source';
UPDATE categories SET image = 'cat_docs.gif' WHERE name = 'Documentation';

INSERT INTO categories (name, image, cat_desc)
SELECT 'Test Files', 'cat_test.gif', 'Synthetic legal files used for tracker validation.'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Test Files');

INSERT INTO categories (name, image, cat_desc)
SELECT 'Open Source', 'cat_software.gif', 'Open-source software and freely distributable material.'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Open Source');

INSERT INTO categories (name, image, cat_desc)
SELECT 'Documentation', 'cat_docs.gif', 'Public-domain or self-created documentation fixtures.'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Documentation');
