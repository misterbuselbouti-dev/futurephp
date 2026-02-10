# شرح حل مشكلة SQL

## ❌ **المشكلة:**
المحرر يعتبر التعليقات كجزء من استعلام SQL ويسبب خطأ في التحليل.

## ✅ **الحل الصحيح:**

### **الطريقة القديمة (تسبب الخطأ):**
```sql
-- قبل: WHERE da.statut IN ('Validé', 'En attente', 'Brouillon')
-- بعد: WHERE da.statut IN ('Validé', 'En attente')
GROUP BY da.id
HAVING dp_count = 0
```

### **الطريقة الجديدة (الصحيحة):**
لا تستخدم التعليقات في استعلامات SQL المعقدة. استخدم استعلامات بسيطة منفصلة.

#### **1. في PHP (الطريقة الموصى بها):**
```php
// الخطوة 1: جلب DA الصالحة
$stmt = $conn->query("
    SELECT id, ref_da, demandeur, statut, priorite
    FROM demandes_achat 
    WHERE statut IN ('Validé', 'En attente')
    ORDER BY date_creation DESC
");

// الخطوة 2: التحقق من عدم وجود DP
foreach ($all_da as $da) {
    $stmt_dp = $conn->prepare("SELECT COUNT(*) as count FROM demandes_prix WHERE da_id = ?");
    $stmt_dp->execute([$da['id']]);
    $dp_count = $stmt_dp->fetch()['count'];
    
    if ($dp_count == 0) {
        $available_da[] = $da;
    }
}
```

#### **2. إذا كان يجب استخدام استعلام واحد:**
```sql
SELECT 
    da.id,
    da.ref_da,
    da.demandeur,
    da.statut,
    da.priorite,
    (SELECT COUNT(*) FROM demandes_prix WHERE da_id = da.id) as dp_count
FROM demandes_achat da
WHERE da.statut IN ('Validé', 'En attente')
HAVING (SELECT COUNT(*) FROM demandes_prix WHERE da_id = da.id) = 0
ORDER BY da.date_creation DESC
```

## 🎯 **لماذا الحل الجديد أفضل؟**

1. **لا يسبب خطأ** في المحرر
2. **أكثر استقرارية** عبر إصدارات MySQL المختلفة
3. **أسهل في القراءة** والصيانة
4. **أداء جيد** مع البيانات الصغيرة

## 🔧 **كيفية تجنب المشكلة:**

1. **لا تستخدم** التعليقات داخل استعلامات SQL المعقدة
2. **استخدم** استعلامات بسيطة ومنفصلة
3. **افصل** منطق التحقق في PHP
4. **اختبر** الاستعلامات في phpMyAdmin أولاً
