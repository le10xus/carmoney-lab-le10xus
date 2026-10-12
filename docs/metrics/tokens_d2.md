без индекса:
6 steps · $0.00
In 53,300 · Out 2,147 · Reason 2,013
Cache R 108,288 · W 0 · Hit Rate 67.0%

ast-index:
11 steps · $0.00
In 103,116 · Out 1,797 · Reason 2,972
Cache R 183,552 · W 0 · Hit Rate 64.0%

ast-index stats 2>&1 | head -20
ast-index rebuild 2>&1 | tail -20
ast-index symbol "mileage" 2>&1; echo "---"; ast-index symbol "max_mileage_km" 2>&1
ast-index usages "mileage" 2>&1; echo "---USAGES max_mileage_km---"; ast-index usages "max_mileage_km" 2>&1; echo "---SEARCH mileage---"; ast-index search "mileage" 2>&1; echo "---SEARCH max_mileage_km---"; ast-index search "max_mileage_km" 2>&1
ast-index outline backend/src/Domain/AssessmentService.php 2>&1; echo "---"; ast-index outline backend/src/Domain/ApplicationValidator.php 2>&1; echo "---"; ast-index outline backend/src/Domain/MileageRule.php 2>&1; echo "---"; ast-index outline backend/src/AppFactory.php 2>&1; echo "---"; ast-index outline backend/src/Repository/ApplicationRepository.php 2>&1; echo "---"; ast-index outline tests/Unit/AssessmentServiceTest.php 2>&1; echo "---"; ast-index outline tests/Unit/MileageRuleTest.php 2>&1
ast-index outline tests/Unit/ApplicationValidatorTest.php 2>&1

агент звал ast-index: да