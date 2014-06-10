<?php
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The mod_game course module viewed event.
 *
 * @package    mod_game
 * @copyright  2014 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_game\event;
defined('MOODLE_INTERNAL') || die();

/**
 * The mod_game course module viewed event class.
 *
 * @package    mod_game
 * @since      Moodle 2.6
 * @copyright  2014 Vasilis Daloukas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {
    /**
     * Create instance of event.
     *
     * @since Moodle 2.7
     *
     * @param \stdClass $game
     * @param \context_module $context
     * @return course_module_viewed
     */
    public static function viewed(\stdClass $game, \context_module $context) {
        $data = array(
            'context' => $context,
            'objectid' => $game->id
        );
        /** @var course_module_viewed $event */
        $event = self::create($data);
        $event->add_record_snapshot('game', $game);
        return $event;
    }

    public static function played(\stdClass $game, \context_module $context) {
        $data = array(
            'context' => $context,
            'objectid' => $game->id
        );
        /** @var course_module_viewed $event */
        $event = self::create($data);
        $event->add_record_snapshot('game', $game);
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'game';
    }
}
