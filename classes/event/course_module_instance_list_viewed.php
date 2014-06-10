<?php
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The mod_game instance list viewed event.
 *
 * @package    mod_game
 * @copyright  2014 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_game\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_game instance list viewed event class.
 *
 * @package    mod_game
 * @since      Moodle 2.7
 * @copyright  2014 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_instance_list_viewed extends \core\event\course_module_instance_list_viewed {
    /**
     * Create the event from course record.
     *
     * @param \stdClass $course
     * @return course_module_instance_list_viewed
     */
    public static function create_from_course(\stdClass $course) {
        $params = array(
            'context' => \context_course::instance($course->id)
        );
        $event = \mod_game\event\course_module_instance_list_viewed::create($params);
        $event->add_record_snapshot('course', $course);
        return $event;
    }}

