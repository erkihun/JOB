<?php

return [
    'application_submitted' => [
        'subject' => 'የማመልከቻ መቀበያ ማረጋገጫ – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ያቀረቡት የስራ ማመልከቻ በተሳካ ሁኔታ ደርሶናል። የማመልከቻ ማጣቀሻ ቁጥርዎ :reference ነው።',
        'closing' => 'ማመልከቻዎ በተቀመጠው የምልመላ ሂደት መሠረት ይገመገማል፤ ቀጣይ ዝማኔ ሲኖር እናሳውቅዎታለን።',
    ],
    'correction_required' => [
        'subject' => 'የማመልከቻ ማስተካከያ ያስፈልጋል – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ያቀረቡት የስራ ማመልከቻ ተጨማሪ ማስተካከያ ይፈልጋል። እባክዎ ወደ መለያዎ ገብተው የተጠየቀውን መረጃ ወይም ሰነድ ያዘምኑ።',
        'remark' => 'የገምጋሚ ማስታወሻ፦ :remark',
        'closing' => 'እባክዎ የተጠየቀውን ማስተካከያ ከተወሰነው ቀነ ገደብ በፊት ያጠናቁ።',
    ],
    'screening_passed' => [
        'subject' => 'የመጀመሪያ ማጣሪያ ውጤት – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'እንኳን ደስ አለዎ። ለ :vacancy ያቀረቡት የስራ ማመልከቻ የመጀመሪያ ማጣሪያን አልፏል።',
        'closing' => 'ቀጣዩን የምልመላ ደረጃ የሚመለከቱ መመሪያዎች በቀጣይ ይላክልዎታል።',
    ],
    'screening_failed' => [
        'subject' => 'የማመልከቻ ውጤት – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ስላመለከቱ እናመሰግናለን። ከተደረገው ጥንቃቄ ያለው ግምገማ በኋላ፣ ማመልከቻዎ ለዚህ ክፍት የስራ መደብ የተጠየቁትን መስፈርቶች አላሟላም።',
        'closing' => 'ወደፊት ለሚታወጁ ተስማሚ ክፍት የስራ መደቦች እንዲያመለክቱ እናበረታታለን።',
    ],
    'exam_invitation' => [
        'subject' => 'የፈተና ጥሪ – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy የምልመላ ፈተና እንዲወስዱ ተጋብዘዋል።',
        'details' => 'ቀን፦ :date | ሰዓት፦ :time | ቦታ፦ :venue',
        'instructions' => 'መመሪያ፦ :instructions',
        'closing' => 'እባክዎ ከተያዘው ሰዓት ቢያንስ 15 ደቂቃ ቀድመው ከሕጋዊ መታወቂያዎ ጋር ይገኙ።',
    ],
    'interview_invitation' => [
        'subject' => 'የቃለ መጠይቅ ጥሪ – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy የምልመላ ቃለ መጠይቅ እንዲካፈሉ ተጋብዘዋል።',
        'details' => 'ቀን፦ :date | ሰዓት፦ :time | ቦታ፦ :venue',
        'instructions' => 'መመሪያ፦ :instructions',
        'closing' => 'እባክዎ በተገለጸው ቀን፣ ሰዓት እና ቦታ ላይ ይገኙ።',
    ],
    'selected' => [
        'subject' => 'እንኳን ደስ አለዎ – ለ :vacancy ተመርጠዋል',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy በተካሄደው የምልመላ ሂደት ተመርጠዋል ብለን ለማሳወቅ ደስ ብሎናል።',
        'closing' => 'የሰው ሀብት ቡድናችን ቀጣይ የቅጥር ሂደትን የሚመለከቱ ዝርዝሮችን ያሳውቅዎታል።',
    ],
    'waitlisted' => [
        'subject' => 'የማመልከቻ ሁኔታ – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy በተካሄደው የምልመላ ሂደት በተጠባባቂ ዝርዝር ተካትተዋል።',
        'closing' => 'ቦታ ወይም ተጨማሪ እድል ሲኖር በቀጣይ እናሳውቅዎታለን።',
    ],
    'not_selected' => [
        'subject' => 'የማመልከቻ ውጤት – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ላሳዩት ፍላጎት እናመሰግናለን። ከተደረገው ጥንቃቄ ያለው ግምገማ በኋላ፣ ለዚህ ዙር ሌሎች እጩዎች ተመርጠዋል።',
        'closing' => 'በቀጣይ የስራ እድሎች ላይ እንዲሳተፉ እናበረታታለን።',
    ],
];