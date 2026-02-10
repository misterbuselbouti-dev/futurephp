<?php
// FUTURE AUTOMOTIVE - FTP Troubleshooting Guide
// دليل حل مشاكل FTP

echo "<h2>🔧 دليل حل مشاكل FTP</h2>";

echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>🚨 المشكلة: فشل تسجيل الدخول إلى FTP</h3>";
echo "<p>هذه هي أكثر المشاكل شيوعاً وحلولها:</p>";
echo "</div>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;'>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
echo "<h4>❌ المشكلة 1: اسم المستخدم غير صحيح</h4>";
echo "<p><strong>السبب:</strong> استخدام ftp:// في اسم المستخدم</p>";
echo "<p><strong>الحل:</strong> استخدم فقط عنوان IP أو اسم النطاق</p>";
echo "<div style='background: #fff; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
echo "<strong>❌ خطأ:</strong> ftp://92.113.18.108<br>";
echo "<strong>✅ صحيح:</strong> 92.113.18.108";
echo "</div>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #dc3545;'>";
echo "<h4>❌ المشكلة 2: كلمة المرور غير صحيحة</h4>";
echo "<p><strong>السبب:</strong> كلمة مرور قديمة أو مغيرة</p>";
echo "<p><strong>الحل:</strong> تحقق من كلمة المرور في Hostinger</p>";
echo "<ol>";
echo "<li>سجل دخول إلى لوحة تحكم Hostinger</li>";
echo "<li>اذهب إلى Hosting → Manage</li>";
echo "<li>ابحث عن FTP Accounts</li>";
echo "<li>إعادة تعيين كلمة المرور إذا لزم الأمر</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h4>⚠️ المشكلة 3: حساب FTP غير نشط</h4>";
echo "<p><strong>السبب:</strong> حساب FTP معطل أو منتهي الصلاحية</p>";
echo "<p><strong>الحل:</strong> تفعيل حساب FTP</p>";
echo "<ol>";
echo "<li>اذهب إلى FTP Accounts في Hostinger</li>";
echo "<li>تأكد من أن حساب FTP نشط</li>";
echo "<li>إذا كان معطلاً، قم بتفعيله</li>";
echo "<li>أعد إنشاء حساب جديد إذا لزم الأمر</li>";
echo "</ol>";
echo "</div>";

echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107;'>";
echo "<h4>⚠️ المشكلة 4: مشكلة في المجلد البعيد</h4>";
echo "<p><strong>السبب:</strong> المجلد /public_html/futureautomotive غير موجود</p>";
echo "<p><strong>الحل:</strong> إنشاء المجلد يدوياً</p>";
echo "<ol>";
echo "<li>سجل دخول إلى Hostinger File Manager</li>";
echo "<li>اذهب إلى public_html</li>";
echo "<li>أنشئ مجلد futureautomotive</li>";
echo "<li>تأكد من صلاحيات الكتابة</li>";
echo "</ol>";
echo "</div>";

echo "</div>";

echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
echo "<h3>🔧 خطوات الحل المقترحة:</h3>";
echo "<ol>";
echo "<li><strong>أولاً:</strong> شغل اختبار FTP</li>";
echo "<li><strong>ثانياً:</strong> راجع نتائج الاختبار</li>";
echo "<li><strong>ثالثاً:</strong> طبق الحل المناسب</li>";
echo "<li><strong>أخيراً:</strong> أعد اختبار الاتصال</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='ftp_test.php' class='btn' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔧 اختبار FTP</a>";
echo "<a href='ftp_setup.php' class='btn' style='background: #6c757d; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>⚙️ إعادة الإعداد</a>";
echo "</div>";

echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<h4>💡 معلومات إضافية:</h4>";
echo "<ul>";
echo "<li>بعض مضيفي Hostinger يستخدمون منفذ 21 للـ FTP</li>";
echo "<li>قد تحتاج إلى استخدام SSL FTP (منفذ 990)</li>";
echo "<li>تأكد من أن جدار الحماية لا يمنع FTP</li>";
echo "<li>جرب استخدام FTPS إذا كان FTP لا يعمل</li>";
echo "</ul>";
echo "</div>";
?>
