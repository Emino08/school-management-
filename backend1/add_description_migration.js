const mysql = require('mysql2/promise');
require('dotenv').config();

async function addDescriptionColumn() {
  console.log('========================================');
  console.log('Adding Description Column to Subjects');
  console.log('========================================\n');

  const connection = await mysql.createConnection({
    host: process.env.DB_HOST || 'localhost',
    port: process.env.DB_PORT || 4306,
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '1212',
    database: process.env.DB_NAME || 'school_management',
  });

  try {
    console.log('Connected to database...');

    // Add description column
    console.log('Adding description column...');
    await connection.query(`
      ALTER TABLE subjects 
      ADD COLUMN description TEXT NULL AFTER sessions
    `);

    console.log('✅ Description column added successfully!\n');

    // Verify the change
    console.log('Table structure updated:');
    const [rows] = await connection.query('DESCRIBE subjects');
    console.table(rows);

    console.log('\n========================================');
    console.log('SUCCESS! Migration completed');
    console.log('========================================');

  } catch (error) {
    if (error.code === 'ER_DUP_FIELDNAME') {
      console.log('⚠️  Description column already exists!');
      
      // Show current table structure
      console.log('\nCurrent table structure:');
      const [rows] = await connection.query('DESCRIBE subjects');
      console.table(rows);
    } else {
      console.error('❌ Error:', error.message);
      console.error('Full error:', error);
    }
  } finally {
    await connection.end();
  }
}

addDescriptionColumn();
