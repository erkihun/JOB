<?php

return [
    'application_submitted' => [
        'subject' => 'ማመልከቻ ተቀብሏል – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ያቀረቡት ማመልከቻ ደርሷል። ማረጋገጫ ቁጥርዎ :reference ነው።',
        'closing' => 'ማመልከቻዎን እናጠናለን፤ ዝማኔ ስናደርግ እናሳውቅዎታለን።',
    ],
    'correction_required' => [
        'subject' => 'ማስተካከያ ያስፈልጋል – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ላቀረቡት ማመልከቻ ማስተካከያ ያስፈልጋል። ወደ መለያዎ ገብተው ማመልከቻዎን ያዘምኑ።',
        'remark' => 'የገምጋሚ ማስታወሻ: :remark',
        'closing' => 'እባክዎን ከቀኑ ማብቂያ በፊት ማስተካከያ ያድርጉ።',
    ],
    'screening_passed' => [
        'subject' => 'ማጣሪያ አልፏል – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'እንኳን ደስ አለዎ! ለ :vacancy ያቀረቡት ማመልከቻ የመጀመሪያ ማጣሪያ አልፏል።',
        'closing' => 'ተጨማሪ ትዕዛዞች ይደርሱዎታል።',
    ],
    'screening_failed' => [
        'subject' => 'የማመልከቻ ውጤት – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ስላመለከቱ እናመሰግናለን። ከጥንቃቄ ግምገማ በኋላ ማመልከቻዎ ለቦታው አላሟሳም።',
        'closing' => 'ወደፊት ለሚቀርቡ ክፍት ቦታዎች እንዲያመለክቱ እናበረታታለን።',
    ],
    'exam_invitation' => [
        'subject' => 'የፈተና ግብዣ – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ፈተና ለመቀመጥ ተጋብዘዋል።',
        'details' => 'ቀን: :date | ሰዓት: :time | ቦታ: :venue',
        'instructions' => 'መመሪያ: :instructions',
        'closing' => 'እባክዎን ከ15 ደቂቃ አስቀድሞ ከህጋዊ መታወቂያ ጋር ይምጡ።',
    ],
    'interview_invitation' => [
        'subject' => 'የቃለ መጠይቅ ግብዣ – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ቃለ መጠይቅ ለመቀመጥ ተጋብዘዋል።',
        'details' => 'ቀን: :date | ሰዓት: :time | ቦታ: :venue',
        'instructions' => 'መመሪያ: :instructions',
        'closing' => 'እባክዎን ምላሽዎን ያሳውቁን።',
    ],
    'selected' => [
        'subject' => 'እንኳን ደስ አለዎ – ለ :vacancy ተምረዋል',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ተምረዋል ብለን ለማሳወቅ ደስ ብሎናል።',
        'closing' => 'የሰው ሀብት ቡድናችን ስለ ሥርዓተ ምዝገባ ዝርዝር ያሳውቅዎታል።',
    ],
    'waitlisted' => [
        'subject' => 'የማመልከቻ ሁኔታ – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy በተጠባባቂ ዝርዝር ተካትተዋል።',
        'closing' => 'ቦታ ሲኖር እናሳውቅዎታለን።',
    ],
    'not_selected' => [
        'subject' => 'የማመልከቻ ውጤት – :vacancy',
        'greeting' => 'ውድ :name,',
        'body' => 'ለ :vacancy ላሳዩት ፍላጎት እናመሰግናለን። ከጥንቃቄ ግምት በኋላ ሌሎች እጩዎችን መርጠናል።',
        'closing' => 'ወደፊት ስኬትን እንመኝልዎ።',
    ],
];
