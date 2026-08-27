-- Lab-only fixture. Do not apply to a production database automatically.
INSERT INTO categories (name, image, cat_desc)
SELECT 'Test Files', 'test.gif', 'Synthetic legal files used for tracker validation.'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Test Files');

INSERT INTO categories (name, image, cat_desc)
SELECT 'Open Source', 'software.gif', 'Open-source software and freely distributable material.'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Open Source');

INSERT INTO categories (name, image, cat_desc)
SELECT 'Documentation', 'docs.gif', 'Public-domain or self-created documentation fixtures.'
WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Documentation');
