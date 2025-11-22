# 🚀 QUICK MIGRATION GUIDE

## One-Command Migration (If you have SSH access)

```bash
# 1. Backup
mysqldump -u u232752871_boschool -p u232752871_sms > backup.sql

# 2. Migrate
mysql -u u232752871_boschool -p u232752871_sms < updated.sql

# 3. Verify
mysql -u u232752871_boschool -p u232752871_sms < verify_after_migration.sql
```

---

## Using cPanel/phpMyAdmin

1. **Backup:** cPanel → phpMyAdmin → Export → Go
2. **Migrate:** cPanel → phpMyAdmin → Import → Choose `updated.sql` → Go
3. **Verify:** Run `verify_after_migration.sql` via Import

---

## What Changes?

| Item | Before | After |
|------|--------|-------|
| **students.first_name** | ❌ Doesn't exist | ✅ Exists & populated |
| **students.last_name** | ❌ Doesn't exist | ✅ Exists & populated |
| **teacher_subject_assignments** | ❌ Doesn't exist | ✅ New table |
| **notification_reads** | ❌ Doesn't exist | ✅ New table |
| **password_resets** | ❌ Doesn't exist | ✅ New table |
| **email_logs** | ❌ Doesn't exist | ✅ New table |
| **All existing data** | ✅ Preserved | ✅ Still there! |

---

## Safety Checks

✅ **Idempotent** - Can run multiple times safely  
✅ **Transactional** - All or nothing  
✅ **Zero data loss** - Existing data preserved  
✅ **Backward compatible** - Old code still works  

---

## After Migration

Your backend code will now work with:
- ✅ Separate first/last names
- ✅ Teacher class assignments
- ✅ Notification system
- ✅ Password reset via email
- ✅ Email configuration
- ✅ System settings

---

## Emergency Rollback

```bash
mysql -u u232752871_boschool -p u232752871_sms < backup.sql
```

---

## Verify Success

```sql
-- Check student names
SELECT id, name, first_name, last_name FROM students LIMIT 5;

-- Check new tables
SHOW TABLES;

-- Count records (should match before)
SELECT COUNT(*) FROM students;
```

---

## Need Help?

1. Read full `README.md`
2. Check migration logs
3. Run verification scripts
4. Restore from backup if needed

---

## Files in This Folder

- **updated.sql** ⭐ Main migration (run this)
- **verify_before_migration.sql** - Check before
- **verify_after_migration.sql** - Check after
- **README.md** - Full documentation
- **QUICK_GUIDE.md** - This file

---

**⚠️ ALWAYS BACKUP FIRST!**

```bash
mysqldump -u u232752871_boschool -p u232752871_sms > backup_$(date +%Y%m%d).sql
```

Then proceed with confidence! 🚀
